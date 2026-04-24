# File: sheener/PY/waste_excel_importer.py
"""
Waste Management System - Excel Import Module
Imports vendor Excel reports into the waste management database
"""

import mysql.connector
from mysql.connector import Error
import pandas as pd
import os
from datetime import datetime
import json
import re

# Database connection credentials
host = 'localhost'
db = 'sheener'
user = 'root'
passwd = ''

class WasteExcelImporter:
    def __init__(self):
        self.connection = None
        self.cursor = None
        
    def connect(self):
        """Establish database connection"""
        try:
            self.connection = mysql.connector.connect(
                host=host,
                user=user,
                password=passwd,
                database=db,
                charset='utf8mb4'
            )
            self.cursor = self.connection.cursor()
            return True
        except Error as e:
            print(f"Database connection error: {e}")
            return False
    
    def disconnect(self):
        """Close database connection"""
        if self.cursor:
            self.cursor.close()
        if self.connection and self.connection.is_connected():
            self.connection.close()
    
    def normalize_column_name(self, col_name):
        """Normalize column names to handle variations"""
        if pd.isna(col_name):
            return None
        
        col_lower = str(col_name).strip().lower()
        
        # Common column name mappings
        mappings = {
            'date': ['date', 'collection date', 'pickup date', 'disposal date'],
            'vendor': ['vendor', 'vendor name', 'company', 'contractor'],
            'site': ['site', 'location', 'facility', 'site name', 'collection site'],
            'category': ['category', 'waste type', 'waste category', 'type'],
            'quantity': ['quantity', 'qty', 'amount', 'weight', 'kg', 'tons'],
            'weight': ['weight', 'kg', 'kilograms', 'weight (kg)'],
            'volume': ['volume', 'liters', 'cubic meters', 'm3'],
            'cost': ['cost', 'price', 'amount', 'fee', 'charge'],
            'reference': ['reference', 'ref', 'ref no', 'collection ref', 'invoice no'],
            'method': ['method', 'disposal method', 'treatment method'],
            'notes': ['notes', 'remarks', 'comments', 'description']
        }
        
        for key, variations in mappings.items():
            if any(v in col_lower for v in variations):
                return key
        
        return col_lower.replace(' ', '_').replace('-', '_')
    
    def find_vendor(self, vendor_name):
        """Find or create vendor by name"""
        if not vendor_name or pd.isna(vendor_name):
            return None
        
        vendor_name = str(vendor_name).strip()
        
        # Try to find existing vendor
        self.cursor.execute("""
            SELECT vendor_id FROM waste_vendors 
            WHERE vendor_name = %s OR vendor_code = %s
        """, (vendor_name, vendor_name))
        result = self.cursor.fetchone()
        
        if result:
            return result[0]
        
        # Create new vendor if not found
        vendor_code = re.sub(r'[^A-Z0-9]', '', vendor_name.upper())[:20]
        self.cursor.execute("""
            INSERT INTO waste_vendors (vendor_name, vendor_code, status)
            VALUES (%s, %s, 'Active')
        """, (vendor_name, vendor_code))
        self.connection.commit()
        return self.cursor.lastrowid
    
    def find_site(self, site_name):
        """Find or create collection site by name"""
        if not site_name or pd.isna(site_name):
            return None
        
        site_name = str(site_name).strip()
        
        # Try to find existing site
        self.cursor.execute("""
            SELECT site_id FROM waste_collection_sites 
            WHERE site_name = %s OR site_code = %s
        """, (site_name, site_name))
        result = self.cursor.fetchone()
        
        if result:
            return result[0]
        
        # Create new site if not found
        site_code = re.sub(r'[^A-Z0-9]', '', site_name.upper())[:20]
        self.cursor.execute("""
            INSERT INTO waste_collection_sites (site_name, site_code, status)
            VALUES (%s, %s, 'Active')
        """, (site_name, site_code))
        self.connection.commit()
        return self.cursor.lastrowid
    
    def find_category(self, category_name):
        """Find waste category by name or code (supports subcategories)"""
        if not category_name or pd.isna(category_name):
            return None
        
        category_name = str(category_name).strip()
        
        # Try exact match first (including subcategories)
        self.cursor.execute("""
            SELECT category_id FROM waste_categories 
            WHERE category_name = %s OR category_code = %s
            ORDER BY parent_category_id IS NULL DESC
            LIMIT 1
        """, (category_name, category_name))
        result = self.cursor.fetchone()
        
        if result:
            return result[0]
        
        # Try partial match (prefer more specific matches)
        self.cursor.execute("""
            SELECT category_id FROM waste_categories 
            WHERE category_name LIKE %s OR category_code LIKE %s
            ORDER BY 
                CASE WHEN category_name = %s THEN 1 ELSE 2 END,
                parent_category_id IS NULL DESC
            LIMIT 1
        """, (f'%{category_name}%', f'%{category_name}%', category_name))
        result = self.cursor.fetchone()
        
        if result:
            return result[0]
        
        # Try matching with common variations
        variations = {
            'cardboard': 'Recyclable - Cardboard',
            'paper': 'Recyclable - Paper',
            'plastic': 'Recyclable - Clear Clean Plastics',
            'glass': 'Recyclable - Glass',
            'metal': 'Recyclable - Metal',
            'aluminum': 'Recyclable - Aluminum',
            'flammable': 'Hazardous - Flammables',
            'explosive': 'Hazardous - Explosives',
            'pressurized': 'Hazardous - Pressurized',
            'toxic': 'Hazardous - Toxic',
            'corrosive': 'Hazardous - Corrosive',
            'liquid': 'Hazardous - Liquids',
            'gas': 'Hazardous - Gases',
            'solid': 'Hazardous - Solids',
            'compost': 'Non-Hazardous - Compostable',
            'food waste': 'Compostable - Food Waste',
            'general': 'Non-Hazardous - General Waste',
        }
        
        category_lower = category_name.lower()
        for key, mapped_name in variations.items():
            if key in category_lower:
                self.cursor.execute("""
                    SELECT category_id FROM waste_categories 
                    WHERE category_name = %s
                    LIMIT 1
                """, (mapped_name,))
                result = self.cursor.fetchone()
                if result:
                    return result[0]
        
        return None
    
    def parse_date(self, date_value):
        """Parse various date formats"""
        if pd.isna(date_value):
            return None
        
        try:
            if isinstance(date_value, str):
                # Try common date formats
                for fmt in ['%Y-%m-%d', '%d/%m/%Y', '%m/%d/%Y', '%d-%m-%Y', '%Y/%m/%d']:
                    try:
                        return datetime.strptime(date_value, fmt).date()
                    except:
                        continue
                # Try pandas parsing
                return pd.to_datetime(date_value).date()
            elif isinstance(date_value, datetime):
                return date_value.date()
            else:
                return pd.to_datetime(date_value).date()
        except:
            return None
    
    def parse_decimal(self, value):
        """Parse decimal values, handling various formats"""
        if pd.isna(value):
            return None
        
        try:
            if isinstance(value, str):
                # Remove common formatting
                value = value.replace(',', '').replace('$', '').replace('€', '').strip()
            return float(value)
        except:
            return None
    
    def import_excel_file(self, file_path, vendor_id=None, imported_by=None):
        """
        Import Excel file into database
        
        Args:
            file_path: Path to Excel file
            vendor_id: Optional vendor ID (if None, will try to detect from file)
            imported_by: User ID who imported the file
        
        Returns:
            dict with import results
        """
        if not self.connect():
            return {'success': False, 'error': 'Database connection failed'}
        
        if not os.path.exists(file_path):
            return {'success': False, 'error': f'File not found: {file_path}'}
        
        filename = os.path.basename(file_path)
        rows_imported = 0
        rows_failed = 0
        errors = []
        
        try:
            # Read Excel file
            df = pd.read_excel(file_path, sheet_name=0)
            
            if df.empty:
                return {'success': False, 'error': 'Excel file is empty'}
            
            # Normalize column names
            df.columns = [self.normalize_column_name(col) for col in df.columns]
            
            # Create import record
            self.cursor.execute("""
                INSERT INTO vendor_excel_imports 
                (vendor_id, filename, file_path, import_status, imported_by)
                VALUES (%s, %s, %s, 'Processing', %s)
            """, (vendor_id, filename, file_path, imported_by))
            import_id = self.cursor.lastrowid
            self.connection.commit()
            
            # Process each row
            for idx, row in df.iterrows():
                try:
                    # Extract data from row
                    collection_date = self.parse_date(row.get('date'))
                    if not collection_date:
                        errors.append(f"Row {idx+2}: Missing or invalid date")
                        rows_failed += 1
                        continue
                    
                    # Get or create vendor
                    vendor_name = row.get('vendor')
                    if vendor_id:
                        current_vendor_id = vendor_id
                    elif vendor_name:
                        current_vendor_id = self.find_vendor(vendor_name)
                    else:
                        errors.append(f"Row {idx+2}: Missing vendor information")
                        rows_failed += 1
                        continue
                    
                    # Get or create site
                    site_name = row.get('site')
                    site_id = self.find_site(site_name) if site_name else None
                    
                    # Get collection reference
                    collection_ref = str(row.get('reference', f'IMP-{import_id}-{idx+1}')).strip()
                    
                    # Get totals
                    total_weight = self.parse_decimal(row.get('weight') or row.get('quantity'))
                    total_volume = self.parse_decimal(row.get('volume'))
                    
                    # Create collection record
                    self.cursor.execute("""
                        INSERT INTO waste_collections
                        (collection_date, site_id, vendor_id, collection_reference, 
                         total_weight, total_volume, status)
                        VALUES (%s, %s, %s, %s, %s, %s, 'Completed')
                    """, (collection_date, site_id, current_vendor_id, collection_ref, 
                          total_weight, total_volume))
                    collection_id = self.cursor.lastrowid
                    
                    # Process waste items
                    category_name = row.get('category')
                    if category_name:
                        category_id = self.find_category(category_name)
                        if category_id:
                            quantity = self.parse_decimal(row.get('quantity') or row.get('weight'))
                            weight = self.parse_decimal(row.get('weight'))
                            volume = self.parse_decimal(row.get('volume'))
                            cost = self.parse_decimal(row.get('cost'))
                            
                            disposal_method = row.get('method', 'Recycle')
                            if disposal_method:
                                disposal_method = str(disposal_method).strip().title()
                                if disposal_method not in ['Recycle', 'Landfill', 'Incineration', 'Compost', 'Reuse', 'Other']:
                                    disposal_method = 'Other'
                            else:
                                disposal_method = 'Recycle'
                            
                            notes = str(row.get('notes', '')).strip() if not pd.isna(row.get('notes')) else None
                            
                            self.cursor.execute("""
                                INSERT INTO waste_collection_items
                                (collection_id, category_id, quantity, unit_of_measure,
                                 weight, volume, cost, disposal_method, notes)
                                VALUES (%s, %s, %s, 'kg', %s, %s, %s, %s, %s)
                            """, (collection_id, category_id, quantity, weight, volume, cost, disposal_method, notes))
                    
                    rows_imported += 1
                    self.connection.commit()
                    
                except Exception as e:
                    errors.append(f"Row {idx+2}: {str(e)}")
                    rows_failed += 1
                    self.connection.rollback()
                    continue
            
            # Update import record
            status = 'Completed' if rows_failed == 0 else ('Partial' if rows_imported > 0 else 'Failed')
            error_log = '\n'.join(errors[:100])  # Limit error log size
            
            self.cursor.execute("""
                UPDATE vendor_excel_imports
                SET rows_imported = %s, rows_failed = %s, 
                    import_status = %s, error_log = %s
                WHERE import_id = %s
            """, (rows_imported, rows_failed, status, error_log, import_id))
            self.connection.commit()
            
            return {
                'success': True,
                'import_id': import_id,
                'rows_imported': rows_imported,
                'rows_failed': rows_failed,
                'errors': errors[:20]  # Return first 20 errors
            }
            
        except Exception as e:
            # Update import record with failure
            if 'import_id' in locals():
                self.cursor.execute("""
                    UPDATE vendor_excel_imports
                    SET import_status = 'Failed', error_log = %s
                    WHERE import_id = %s
                """, (str(e), import_id))
                self.connection.commit()
            
            return {'success': False, 'error': str(e)}
        
        finally:
            self.disconnect()

def import_vendor_excel(file_path, vendor_id=None, imported_by=None):
    """
    Convenience function to import Excel file
    
    Args:
        file_path: Path to Excel file
        vendor_id: Optional vendor ID
        imported_by: Optional user ID
    
    Returns:
        dict with import results
    """
    importer = WasteExcelImporter()
    return importer.import_excel_file(file_path, vendor_id, imported_by)

if __name__ == "__main__":
    import sys
    
    if len(sys.argv) < 2:
        print("Usage: python waste_excel_importer.py <excel_file_path> [vendor_id] [user_id]")
        sys.exit(1)
    
    file_path = sys.argv[1]
    vendor_id = int(sys.argv[2]) if len(sys.argv) > 2 else None
    user_id = int(sys.argv[3]) if len(sys.argv) > 3 else None
    
    print(f"Importing Excel file: {file_path}")
    result = import_vendor_excel(file_path, vendor_id, user_id)
    
    if result['success']:
        print(f"✓ Import completed successfully!")
        print(f"  Rows imported: {result['rows_imported']}")
        print(f"  Rows failed: {result['rows_failed']}")
        if result.get('errors'):
            print(f"\nErrors encountered:")
            for error in result['errors']:
                print(f"  - {error}")
    else:
        print(f"✗ Import failed: {result.get('error', 'Unknown error')}")

