# File: sheener/PY/waste_report_generator.py
"""
Waste Management System - Report Generator
Generates extensive reports on waste management data
"""

import mysql.connector
from mysql.connector import Error
import pandas as pd
from datetime import datetime, timedelta
import json
import os

# Database connection credentials
host = 'localhost'
db = 'sheener'
user = 'root'
passwd = ''

class WasteReportGenerator:
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
            self.cursor = self.connection.cursor(dictionary=True)
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
    
    def generate_summary_report(self, start_date=None, end_date=None, vendor_id=None, site_id=None):
        """Generate comprehensive summary report"""
        if not self.connect():
            return None
        
        try:
            # Default to last 30 days if no dates provided
            if not end_date:
                end_date = datetime.now().date()
            if not start_date:
                start_date = end_date - timedelta(days=30)
            
            # Build WHERE clause
            where_conditions = ["wc.collection_date BETWEEN %s AND %s"]
            params = [start_date, end_date]
            
            if vendor_id:
                where_conditions.append("wc.vendor_id = %s")
                params.append(vendor_id)
            
            if site_id:
                where_conditions.append("wc.site_id = %s")
                params.append(site_id)
            
            where_clause = " AND ".join(where_conditions)
            
            # Total collections
            query = f"""
                SELECT 
                    COUNT(DISTINCT wc.collection_id) as total_collections,
                    COUNT(DISTINCT wc.vendor_id) as total_vendors,
                    COUNT(DISTINCT wc.site_id) as total_sites,
                    SUM(wc.total_weight) as total_weight,
                    SUM(wc.total_volume) as total_volume,
                    AVG(wc.total_weight) as avg_weight_per_collection
                FROM waste_collections wc
                WHERE {where_clause}
            """
            self.cursor.execute(query, params)
            summary = self.cursor.fetchone()
            
            # Collections by category (with parent category info)
            query = f"""
                SELECT 
                    wcat.category_name,
                    wcat.category_code,
                    wcat.hazardous,
                    parent.category_name as parent_category_name,
                    parent.category_code as parent_category_code,
                    COUNT(DISTINCT wci.collection_id) as collection_count,
                    SUM(wci.quantity) as total_quantity,
                    SUM(wci.weight) as total_weight,
                    SUM(wci.volume) as total_volume,
                    SUM(wci.cost) as total_cost,
                    AVG(wci.cost) as avg_cost
                FROM waste_collection_items wci
                JOIN waste_collections wc ON wci.collection_id = wc.collection_id
                JOIN waste_categories wcat ON wci.category_id = wcat.category_id
                LEFT JOIN waste_categories parent ON wcat.parent_category_id = parent.category_id
                WHERE {where_clause}
                GROUP BY wcat.category_id, wcat.category_name, wcat.category_code, wcat.hazardous, parent.category_name, parent.category_code
                ORDER BY COALESCE(parent.category_name, wcat.category_name), wcat.category_name, total_weight DESC
            """
            self.cursor.execute(query, params)
            by_category = self.cursor.fetchall()
            
            # Collections by vendor
            query = f"""
                SELECT 
                    wv.vendor_name,
                    wv.vendor_code,
                    COUNT(DISTINCT wc.collection_id) as collection_count,
                    SUM(wc.total_weight) as total_weight,
                    SUM(wc.total_volume) as total_volume,
                    SUM(wci.cost) as total_cost
                FROM waste_collections wc
                JOIN waste_vendors wv ON wc.vendor_id = wv.vendor_id
                LEFT JOIN waste_collection_items wci ON wc.collection_id = wci.collection_id
                WHERE {where_clause}
                GROUP BY wv.vendor_id, wv.vendor_name, wv.vendor_code
                ORDER BY total_weight DESC
            """
            self.cursor.execute(query, params)
            by_vendor = self.cursor.fetchall()
            
            # Collections by site
            query = f"""
                SELECT 
                    wcs.site_name,
                    wcs.site_code,
                    COUNT(DISTINCT wc.collection_id) as collection_count,
                    SUM(wc.total_weight) as total_weight,
                    SUM(wc.total_volume) as total_volume
                FROM waste_collections wc
                JOIN waste_collection_sites wcs ON wc.site_id = wcs.site_id
                WHERE {where_clause}
                GROUP BY wcs.site_id, wcs.site_name, wcs.site_code
                ORDER BY total_weight DESC
            """
            self.cursor.execute(query, params)
            by_site = self.cursor.fetchall()
            
            # Collections by disposal method
            query = f"""
                SELECT 
                    wci.disposal_method,
                    COUNT(DISTINCT wci.collection_id) as collection_count,
                    SUM(wci.quantity) as total_quantity,
                    SUM(wci.weight) as total_weight,
                    SUM(wci.cost) as total_cost
                FROM waste_collection_items wci
                JOIN waste_collections wc ON wci.collection_id = wc.collection_id
                WHERE {where_clause}
                GROUP BY wci.disposal_method
                ORDER BY total_weight DESC
            """
            self.cursor.execute(query, params)
            by_method = self.cursor.fetchall()
            
            # Monthly trends
            query = f"""
                SELECT 
                    DATE_FORMAT(wc.collection_date, '%Y-%m') as month,
                    COUNT(DISTINCT wc.collection_id) as collection_count,
                    SUM(wc.total_weight) as total_weight,
                    SUM(wc.total_volume) as total_volume
                FROM waste_collections wc
                WHERE {where_clause}
                GROUP BY DATE_FORMAT(wc.collection_date, '%Y-%m')
                ORDER BY month
            """
            self.cursor.execute(query, params)
            monthly_trends = self.cursor.fetchall()
            
            # Hazardous vs Non-Hazardous
            query = f"""
                SELECT 
                    wcat.hazardous,
                    COUNT(DISTINCT wci.collection_id) as collection_count,
                    SUM(wci.quantity) as total_quantity,
                    SUM(wci.weight) as total_weight,
                    SUM(wci.cost) as total_cost
                FROM waste_collection_items wci
                JOIN waste_collections wc ON wci.collection_id = wc.collection_id
                JOIN waste_categories wcat ON wci.category_id = wcat.category_id
                WHERE {where_clause}
                GROUP BY wcat.hazardous
            """
            self.cursor.execute(query, params)
            hazardous_breakdown = self.cursor.fetchall()
            
            return {
                'period': {
                    'start_date': str(start_date),
                    'end_date': str(end_date)
                },
                'summary': summary,
                'by_category': by_category,
                'by_vendor': by_vendor,
                'by_site': by_site,
                'by_method': by_method,
                'monthly_trends': monthly_trends,
                'hazardous_breakdown': hazardous_breakdown
            }
            
        except Error as e:
            print(f"Error generating summary report: {e}")
            return None
        finally:
            self.disconnect()
    
    def generate_vendor_report(self, vendor_id, start_date=None, end_date=None):
        """Generate detailed vendor report"""
        if not self.connect():
            return None
        
        try:
            if not end_date:
                end_date = datetime.now().date()
            if not start_date:
                start_date = end_date - timedelta(days=90)
            
            # Vendor information
            self.cursor.execute("""
                SELECT * FROM waste_vendors WHERE vendor_id = %s
            """, (vendor_id,))
            vendor_info = self.cursor.fetchone()
            
            if not vendor_info:
                return None
            
            # Collections for this vendor
            query = """
                SELECT 
                    wc.*,
                    wcs.site_name,
                    wcs.site_code,
                    COUNT(DISTINCT wci.item_id) as item_count,
                    SUM(wci.quantity) as total_item_quantity,
                    SUM(wci.weight) as total_item_weight,
                    SUM(wci.cost) as total_item_cost
                FROM waste_collections wc
                JOIN waste_collection_sites wcs ON wc.site_id = wcs.site_id
                LEFT JOIN waste_collection_items wci ON wc.collection_id = wci.collection_id
                WHERE wc.vendor_id = %s 
                    AND wc.collection_date BETWEEN %s AND %s
                GROUP BY wc.collection_id
                ORDER BY wc.collection_date DESC
            """
            self.cursor.execute(query, (vendor_id, start_date, end_date))
            collections = self.cursor.fetchall()
            
            # Category breakdown
            query = """
                SELECT 
                    wcat.category_name,
                    wcat.category_code,
                    COUNT(DISTINCT wci.collection_id) as collection_count,
                    SUM(wci.quantity) as total_quantity,
                    SUM(wci.weight) as total_weight,
                    SUM(wci.cost) as total_cost
                FROM waste_collection_items wci
                JOIN waste_collections wc ON wci.collection_id = wc.collection_id
                JOIN waste_categories wcat ON wci.category_id = wcat.category_id
                WHERE wc.vendor_id = %s 
                    AND wc.collection_date BETWEEN %s AND %s
                GROUP BY wcat.category_id
                ORDER BY total_weight DESC
            """
            self.cursor.execute(query, (vendor_id, start_date, end_date))
            category_breakdown = self.cursor.fetchall()
            
            # Summary statistics
            total_collections = len(collections)
            total_weight = sum(c['total_weight'] or 0 for c in collections)
            total_cost = sum(c['total_item_cost'] or 0 for c in collections)
            
            return {
                'vendor': vendor_info,
                'period': {
                    'start_date': str(start_date),
                    'end_date': str(end_date)
                },
                'summary': {
                    'total_collections': total_collections,
                    'total_weight': total_weight,
                    'total_cost': total_cost,
                    'avg_weight_per_collection': total_weight / total_collections if total_collections > 0 else 0
                },
                'collections': collections,
                'category_breakdown': category_breakdown
            }
            
        except Error as e:
            print(f"Error generating vendor report: {e}")
            return None
        finally:
            self.disconnect()
    
    def generate_compliance_report(self, start_date=None, end_date=None):
        """Generate compliance report"""
        if not self.connect():
            return None
        
        try:
            if not end_date:
                end_date = datetime.now().date()
            if not start_date:
                start_date = end_date - timedelta(days=90)
            
            # Compliance records
            query = """
                SELECT 
                    wcr.*,
                    wc.collection_reference,
                    wc.collection_date,
                    wv.vendor_name,
                    p.FirstName as reviewer_first_name,
                    p.LastName as reviewer_last_name
                FROM waste_compliance_records wcr
                LEFT JOIN waste_collections wc ON wcr.collection_id = wc.collection_id
                LEFT JOIN waste_vendors wv ON wc.vendor_id = wv.vendor_id
                LEFT JOIN people p ON wcr.reviewer_id = p.people_id
                WHERE wcr.compliance_date BETWEEN %s AND %s
                ORDER BY wcr.compliance_date DESC
            """
            self.cursor.execute(query, (start_date, end_date))
            compliance_records = self.cursor.fetchall()
            
            # Compliance by status
            query = """
                SELECT 
                    status,
                    COUNT(*) as count
                FROM waste_compliance_records
                WHERE compliance_date BETWEEN %s AND %s
                GROUP BY status
            """
            self.cursor.execute(query, (start_date, end_date))
            by_status = self.cursor.fetchall()
            
            # Compliance by type
            query = """
                SELECT 
                    compliance_type,
                    COUNT(*) as count,
                    SUM(CASE WHEN status = 'Compliant' THEN 1 ELSE 0 END) as compliant_count
                FROM waste_compliance_records
                WHERE compliance_date BETWEEN %s AND %s
                GROUP BY compliance_type
            """
            self.cursor.execute(query, (start_date, end_date))
            by_type = self.cursor.fetchall()
            
            # Collections without certificates
            query = """
                SELECT 
                    wc.collection_id,
                    wc.collection_reference,
                    wc.collection_date,
                    wv.vendor_name,
                    wcs.site_name
                FROM waste_collections wc
                JOIN waste_vendors wv ON wc.vendor_id = wv.vendor_id
                JOIN waste_collection_sites wcs ON wc.site_id = wcs.site_id
                LEFT JOIN waste_disposal_certificates wdc ON wc.collection_id = wdc.collection_id
                WHERE wc.collection_date BETWEEN %s AND %s
                    AND wdc.certificate_id IS NULL
                ORDER BY wc.collection_date DESC
            """
            self.cursor.execute(query, (start_date, end_date))
            missing_certificates = self.cursor.fetchall()
            
            return {
                'period': {
                    'start_date': str(start_date),
                    'end_date': str(end_date)
                },
                'compliance_records': compliance_records,
                'by_status': by_status,
                'by_type': by_type,
                'missing_certificates': missing_certificates
            }
            
        except Error as e:
            print(f"Error generating compliance report: {e}")
            return None
        finally:
            self.disconnect()
    
    def export_to_excel(self, report_data, output_path, report_type='summary'):
        """Export report to Excel file"""
        try:
            with pd.ExcelWriter(output_path, engine='openpyxl') as writer:
                if report_type == 'summary':
                    # Summary sheet
                    summary_df = pd.DataFrame([report_data['summary']])
                    summary_df.to_excel(writer, sheet_name='Summary', index=False)
                    
                    # By Category
                    if report_data.get('by_category'):
                        cat_df = pd.DataFrame(report_data['by_category'])
                        cat_df.to_excel(writer, sheet_name='By Category', index=False)
                    
                    # By Vendor
                    if report_data.get('by_vendor'):
                        vendor_df = pd.DataFrame(report_data['by_vendor'])
                        vendor_df.to_excel(writer, sheet_name='By Vendor', index=False)
                    
                    # By Site
                    if report_data.get('by_site'):
                        site_df = pd.DataFrame(report_data['by_site'])
                        site_df.to_excel(writer, sheet_name='By Site', index=False)
                    
                    # By Method
                    if report_data.get('by_method'):
                        method_df = pd.DataFrame(report_data['by_method'])
                        method_df.to_excel(writer, sheet_name='By Disposal Method', index=False)
                    
                    # Monthly Trends
                    if report_data.get('monthly_trends'):
                        trends_df = pd.DataFrame(report_data['monthly_trends'])
                        trends_df.to_excel(writer, sheet_name='Monthly Trends', index=False)
                
                elif report_type == 'vendor':
                    # Vendor Info
                    vendor_df = pd.DataFrame([report_data['vendor']])
                    vendor_df.to_excel(writer, sheet_name='Vendor Info', index=False)
                    
                    # Summary
                    summary_df = pd.DataFrame([report_data['summary']])
                    summary_df.to_excel(writer, sheet_name='Summary', index=False)
                    
                    # Collections
                    if report_data.get('collections'):
                        coll_df = pd.DataFrame(report_data['collections'])
                        coll_df.to_excel(writer, sheet_name='Collections', index=False)
                    
                    # Category Breakdown
                    if report_data.get('category_breakdown'):
                        cat_df = pd.DataFrame(report_data['category_breakdown'])
                        cat_df.to_excel(writer, sheet_name='Category Breakdown', index=False)
                
                elif report_type == 'compliance':
                    # Compliance Records
                    if report_data.get('compliance_records'):
                        comp_df = pd.DataFrame(report_data['compliance_records'])
                        comp_df.to_excel(writer, sheet_name='Compliance Records', index=False)
                    
                    # By Status
                    if report_data.get('by_status'):
                        status_df = pd.DataFrame(report_data['by_status'])
                        status_df.to_excel(writer, sheet_name='By Status', index=False)
                    
                    # By Type
                    if report_data.get('by_type'):
                        type_df = pd.DataFrame(report_data['by_type'])
                        type_df.to_excel(writer, sheet_name='By Type', index=False)
                    
                    # Missing Certificates
                    if report_data.get('missing_certificates'):
                        missing_df = pd.DataFrame(report_data['missing_certificates'])
                        missing_df.to_excel(writer, sheet_name='Missing Certificates', index=False)
            
            return output_path
            
        except Exception as e:
            print(f"Error exporting to Excel: {e}")
            return None
    
    def save_report_metadata(self, report_name, report_type, start_date, end_date, 
                           file_path, generated_by, parameters=None):
        """Save report metadata to database"""
        if not self.connect():
            return None
        
        try:
            params_json = json.dumps(parameters) if parameters else None
            
            self.cursor.execute("""
                INSERT INTO waste_reports
                (report_name, report_type, report_period_start, report_period_end,
                 generated_by, report_file_path, report_parameters)
                VALUES (%s, %s, %s, %s, %s, %s, %s)
            """, (report_name, report_type, start_date, end_date, 
                  generated_by, file_path, params_json))
            self.connection.commit()
            return self.cursor.lastrowid
            
        except Error as e:
            print(f"Error saving report metadata: {e}")
            return None
        finally:
            self.disconnect()

def generate_report(report_type='summary', start_date=None, end_date=None, 
                   vendor_id=None, site_id=None, output_path=None, generated_by=None):
    """
    Convenience function to generate reports
    
    Args:
        report_type: 'summary', 'vendor', or 'compliance'
        start_date: Start date (YYYY-MM-DD)
        end_date: End date (YYYY-MM-DD)
        vendor_id: Vendor ID for vendor report
        site_id: Optional site filter
        output_path: Path to save Excel file
        generated_by: User ID
    
    Returns:
        dict with report data and file path
    """
    generator = WasteReportGenerator()
    
    # Parse dates
    if start_date and isinstance(start_date, str):
        start_date = datetime.strptime(start_date, '%Y-%m-%d').date()
    if end_date and isinstance(end_date, str):
        end_date = datetime.strptime(end_date, '%Y-%m-%d').date()
    
    # Generate report
    if report_type == 'summary':
        report_data = generator.generate_summary_report(start_date, end_date, vendor_id, site_id)
    elif report_type == 'vendor':
        if not vendor_id:
            return {'success': False, 'error': 'Vendor ID required for vendor report'}
        report_data = generator.generate_vendor_report(vendor_id, start_date, end_date)
    elif report_type == 'compliance':
        report_data = generator.generate_compliance_report(start_date, end_date)
    else:
        return {'success': False, 'error': f'Unknown report type: {report_type}'}
    
    if not report_data:
        return {'success': False, 'error': 'Failed to generate report'}
    
    result = {'success': True, 'data': report_data}
    
    # Export to Excel if path provided
    if output_path:
        excel_path = generator.export_to_excel(report_data, output_path, report_type)
        if excel_path:
            result['excel_path'] = excel_path
            
            # Save metadata
            report_name = f"{report_type.title()} Report - {start_date} to {end_date}"
            generator.save_report_metadata(
                report_name, report_type, start_date, end_date,
                excel_path, generated_by,
                {'vendor_id': vendor_id, 'site_id': site_id}
            )
    
    return result

if __name__ == "__main__":
    import sys
    
    if len(sys.argv) < 2:
        print("Usage: python waste_report_generator.py <report_type> [start_date] [end_date] [vendor_id] [output_path]")
        print("Report types: summary, vendor, compliance")
        sys.exit(1)
    
    report_type = sys.argv[1]
    start_date = sys.argv[2] if len(sys.argv) > 2 else None
    end_date = sys.argv[3] if len(sys.argv) > 3 else None
    vendor_id = int(sys.argv[4]) if len(sys.argv) > 4 and sys.argv[4].isdigit() else None
    output_path = sys.argv[5] if len(sys.argv) > 5 else None
    
    result = generate_report(report_type, start_date, end_date, vendor_id, None, output_path)
    
    if result['success']:
        print(f"✓ Report generated successfully!")
        if result.get('excel_path'):
            print(f"  Excel file: {result['excel_path']}")
    else:
        print(f"✗ Report generation failed: {result.get('error', 'Unknown error')}")

