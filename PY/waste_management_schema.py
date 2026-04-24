# File: sheener/PY/waste_management_schema.py
"""
Waste Management System - Database Schema Creation
Creates comprehensive tables for waste management tracking, vendor reports, and analytics
"""

import mysql.connector
from mysql.connector import Error

# Database connection credentials
host = 'localhost'
db = 'sheener'
user = 'root'
passwd = ''  # Use a secure password!

def create_waste_management_schema():
    """Create all tables for the waste management system"""
    
    connection = None
    cursor = None
    
    try:
        # Connect to the MySQL database
        connection = mysql.connector.connect(
            host=host,
            user=user,
            password=passwd,
            database=db,
            charset='utf8mb4'
        )
        cursor = connection.cursor()
        
        print("Connected to database. Creating waste management schema...")
        
        # 1. Waste Categories Table (with subcategory support)
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS waste_categories (
                category_id INT AUTO_INCREMENT PRIMARY KEY,
                category_name VARCHAR(100) NOT NULL,
                category_code VARCHAR(20) UNIQUE,
                description TEXT,
                hazardous BOOLEAN DEFAULT FALSE,
                parent_category_id INT NULL,
                unit_of_measure VARCHAR(20) DEFAULT 'kg',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_category_code (category_code),
                INDEX idx_hazardous (hazardous),
                INDEX idx_parent_category (parent_category_id),
                UNIQUE KEY unique_category_name_parent (category_name, parent_category_id),
                FOREIGN KEY (parent_category_id) REFERENCES waste_categories(category_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        """)
        print("✓ Created waste_categories table with subcategory support")
        
        # 2. Vendors Table
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS waste_vendors (
                vendor_id INT AUTO_INCREMENT PRIMARY KEY,
                vendor_name VARCHAR(255) NOT NULL,
                vendor_code VARCHAR(50) UNIQUE,
                contact_person VARCHAR(255),
                email VARCHAR(255),
                phone VARCHAR(50),
                address TEXT,
                license_number VARCHAR(100),
                license_expiry DATE,
                status ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_vendor_code (vendor_code),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        """)
        print("✓ Created waste_vendors table")
        
        # 3. Waste Collection Sites/Locations
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS waste_collection_sites (
                site_id INT AUTO_INCREMENT PRIMARY KEY,
                site_name VARCHAR(255) NOT NULL,
                site_code VARCHAR(50) UNIQUE,
                address TEXT,
                latitude DECIMAL(10, 8),
                longitude DECIMAL(11, 8),
                site_type ENUM('Facility', 'Warehouse', 'Production', 'Office', 'Other') DEFAULT 'Facility',
                status ENUM('Active', 'Inactive') DEFAULT 'Active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_site_code (site_code),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        """)
        print("✓ Created waste_collection_sites table")
        
        # 4. Waste Collections (Main transaction table)
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS waste_collections (
                collection_id INT AUTO_INCREMENT PRIMARY KEY,
                collection_date DATE NOT NULL,
                site_id INT NOT NULL,
                vendor_id INT NOT NULL,
                collection_reference VARCHAR(100) UNIQUE,
                total_weight DECIMAL(12, 3),
                total_volume DECIMAL(12, 3),
                collection_method ENUM('Pickup', 'Delivery', 'Transfer', 'Other') DEFAULT 'Pickup',
                status ENUM('Scheduled', 'In Transit', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
                notes TEXT,
                created_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (site_id) REFERENCES waste_collection_sites(site_id) ON DELETE RESTRICT,
                FOREIGN KEY (vendor_id) REFERENCES waste_vendors(vendor_id) ON DELETE RESTRICT,
                FOREIGN KEY (created_by) REFERENCES people(people_id) ON DELETE SET NULL,
                INDEX idx_collection_date (collection_date),
                INDEX idx_site_id (site_id),
                INDEX idx_vendor_id (vendor_id),
                INDEX idx_status (status),
                INDEX idx_collection_reference (collection_reference)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        """)
        print("✓ Created waste_collections table")
        
        # 5. Waste Collection Items (Details of what was collected)
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS waste_collection_items (
                item_id INT AUTO_INCREMENT PRIMARY KEY,
                collection_id INT NOT NULL,
                category_id INT NOT NULL,
                quantity DECIMAL(12, 3) NOT NULL,
                unit_of_measure VARCHAR(20) DEFAULT 'kg',
                weight DECIMAL(12, 3),
                volume DECIMAL(12, 3),
                cost DECIMAL(12, 2),
                disposal_method ENUM('Recycle', 'Landfill', 'Incineration', 'Compost', 'Reuse', 'Other') DEFAULT 'Recycle',
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (collection_id) REFERENCES waste_collections(collection_id) ON DELETE CASCADE,
                FOREIGN KEY (category_id) REFERENCES waste_categories(category_id) ON DELETE RESTRICT,
                INDEX idx_collection_id (collection_id),
                INDEX idx_category_id (category_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        """)
        print("✓ Created waste_collection_items table")
        
        # 6. Vendor Excel Imports (Track imported files)
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS vendor_excel_imports (
                import_id INT AUTO_INCREMENT PRIMARY KEY,
                vendor_id INT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                file_path VARCHAR(500),
                import_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                rows_imported INT DEFAULT 0,
                rows_failed INT DEFAULT 0,
                import_status ENUM('Pending', 'Processing', 'Completed', 'Failed', 'Partial') DEFAULT 'Pending',
                error_log TEXT,
                imported_by INT,
                FOREIGN KEY (vendor_id) REFERENCES waste_vendors(vendor_id) ON DELETE RESTRICT,
                FOREIGN KEY (imported_by) REFERENCES people(people_id) ON DELETE SET NULL,
                INDEX idx_vendor_id (vendor_id),
                INDEX idx_import_date (import_date),
                INDEX idx_import_status (import_status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        """)
        print("✓ Created vendor_excel_imports table")
        
        # 7. Waste Disposal Certificates
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS waste_disposal_certificates (
                certificate_id INT AUTO_INCREMENT PRIMARY KEY,
                collection_id INT NOT NULL,
                certificate_number VARCHAR(100) UNIQUE,
                certificate_date DATE,
                certificate_file_path VARCHAR(500),
                issued_by VARCHAR(255),
                expiry_date DATE,
                status ENUM('Valid', 'Expired', 'Pending') DEFAULT 'Pending',
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (collection_id) REFERENCES waste_collections(collection_id) ON DELETE CASCADE,
                INDEX idx_certificate_number (certificate_number),
                INDEX idx_collection_id (collection_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        """)
        print("✓ Created waste_disposal_certificates table")
        
        # 8. Waste Compliance Records
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS waste_compliance_records (
                compliance_id INT AUTO_INCREMENT PRIMARY KEY,
                collection_id INT,
                compliance_type ENUM('Regulatory', 'Environmental', 'Safety', 'Documentation', 'Other') NOT NULL,
                compliance_date DATE NOT NULL,
                requirement_description TEXT,
                status ENUM('Compliant', 'Non-Compliant', 'Pending Review', 'Waived') DEFAULT 'Pending Review',
                reviewer_id INT,
                review_date DATE,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (collection_id) REFERENCES waste_collections(collection_id) ON DELETE SET NULL,
                FOREIGN KEY (reviewer_id) REFERENCES people(people_id) ON DELETE SET NULL,
                INDEX idx_collection_id (collection_id),
                INDEX idx_compliance_type (compliance_type),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        """)
        print("✓ Created waste_compliance_records table")
        
        # 9. Waste Reports (Generated reports metadata)
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS waste_reports (
                report_id INT AUTO_INCREMENT PRIMARY KEY,
                report_name VARCHAR(255) NOT NULL,
                report_type ENUM('Summary', 'Detailed', 'Compliance', 'Vendor', 'Category', 'Custom') NOT NULL,
                report_period_start DATE,
                report_period_end DATE,
                generated_by INT,
                generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                report_file_path VARCHAR(500),
                report_parameters JSON,
                INDEX idx_report_type (report_type),
                INDEX idx_generated_at (generated_at),
                FOREIGN KEY (generated_by) REFERENCES people(people_id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        """)
        print("✓ Created waste_reports table")
        
        # Commit all changes
        connection.commit()
        print("\n✓ All tables created successfully!")
        
        # Insert default categories
        insert_default_data(cursor, connection)
        
    except Error as e:
        print(f"\n✗ Error creating schema: {e}")
        if connection:
            connection.rollback()
    finally:
        if cursor:
            cursor.close()
        if connection and connection.is_connected():
            connection.close()
            print("Database connection closed.")

def insert_default_data(cursor, connection):
    """Insert default waste categories with hierarchical structure"""
    
    try:
        print("\nInserting waste categories with subcategories...")
        
        # First, insert main categories (parent categories)
        main_categories = [
            ('Hazardous Waste', 'HAZ', 'Hazardous materials requiring special handling', True, None, 'kg'),
            ('Non-Hazardous Waste', 'NONHAZ', 'Non-hazardous waste materials', False, None, 'kg'),
        ]
        
        category_ids = {}
        
        # Insert main categories
        for category in main_categories:
            cursor.execute("""
                INSERT IGNORE INTO waste_categories 
                (category_name, category_code, description, hazardous, parent_category_id, unit_of_measure)
                VALUES (%s, %s, %s, %s, %s, %s)
            """, category)
            if cursor.lastrowid:
                category_ids[category[0]] = cursor.lastrowid
            else:
                # If already exists, get the ID
                cursor.execute("SELECT category_id FROM waste_categories WHERE category_name = %s AND parent_category_id IS NULL", (category[0],))
                result = cursor.fetchone()
                if result:
                    category_ids[category[0]] = result[0]
        
        connection.commit()
        
        # Hazardous Waste Subcategories
        hazardous_parent_id = category_ids.get('Hazardous Waste')
        if hazardous_parent_id:
            hazardous_subcategories = [
                ('Hazardous - Liquids', 'HAZ-LIQ', 'Hazardous liquid waste', True, hazardous_parent_id, 'L'),
                ('Hazardous - Gases', 'HAZ-GAS', 'Hazardous gas waste', True, hazardous_parent_id, 'm³'),
                ('Hazardous - Solids', 'HAZ-SOL', 'Hazardous solid waste', True, hazardous_parent_id, 'kg'),
            ]
            
            hazardous_sub_ids = {}
            for subcat in hazardous_subcategories:
                cursor.execute("""
                    INSERT IGNORE INTO waste_categories 
                    (category_name, category_code, description, hazardous, parent_category_id, unit_of_measure)
                    VALUES (%s, %s, %s, %s, %s, %s)
                """, subcat)
                if cursor.lastrowid:
                    hazardous_sub_ids[subcat[0]] = cursor.lastrowid
                else:
                    cursor.execute("SELECT category_id FROM waste_categories WHERE category_name = %s AND parent_category_id = %s", 
                                 (subcat[0], hazardous_parent_id))
                    result = cursor.fetchone()
                    if result:
                        hazardous_sub_ids[subcat[0]] = result[0]
            
            connection.commit()
            
            # Hazardous Solids - Further subcategories
            hazardous_solids_id = hazardous_sub_ids.get('Hazardous - Solids')
            if hazardous_solids_id:
                solids_subcategories = [
                    ('Hazardous - Flammables', 'HAZ-FLAM', 'Flammable hazardous materials', True, hazardous_solids_id, 'kg'),
                    ('Hazardous - Explosives', 'HAZ-EXP', 'Explosive hazardous materials', True, hazardous_solids_id, 'kg'),
                    ('Hazardous - Pressurized', 'HAZ-PRES', 'Pressurized hazardous containers', True, hazardous_solids_id, 'kg'),
                    ('Hazardous - Toxic', 'HAZ-TOX', 'Toxic hazardous materials', True, hazardous_solids_id, 'kg'),
                    ('Hazardous - Corrosive', 'HAZ-COR', 'Corrosive hazardous materials', True, hazardous_solids_id, 'kg'),
                    ('Hazardous - Reactive', 'HAZ-REAC', 'Reactive hazardous materials', True, hazardous_solids_id, 'kg'),
                ]
                
                for subcat in solids_subcategories:
                    cursor.execute("""
                        INSERT IGNORE INTO waste_categories 
                        (category_name, category_code, description, hazardous, parent_category_id, unit_of_measure)
                        VALUES (%s, %s, %s, %s, %s, %s)
                    """, subcat)
            
            connection.commit()
        
        # Non-Hazardous Waste Subcategories
        nonhazardous_parent_id = category_ids.get('Non-Hazardous Waste')
        if nonhazardous_parent_id:
            nonhazardous_subcategories = [
                ('Non-Hazardous - Compostable', 'NON-COMP', 'Compostable organic waste', False, nonhazardous_parent_id, 'kg'),
                ('Non-Hazardous - Recyclable', 'NON-REC', 'Recyclable materials', False, nonhazardous_parent_id, 'kg'),
                ('Non-Hazardous - General Waste', 'NON-GEN', 'General non-hazardous waste', False, nonhazardous_parent_id, 'kg'),
            ]
            
            nonhazardous_sub_ids = {}
            for subcat in nonhazardous_subcategories:
                cursor.execute("""
                    INSERT IGNORE INTO waste_categories 
                    (category_name, category_code, description, hazardous, parent_category_id, unit_of_measure)
                    VALUES (%s, %s, %s, %s, %s, %s)
                """, subcat)
                if cursor.lastrowid:
                    nonhazardous_sub_ids[subcat[0]] = cursor.lastrowid
                else:
                    cursor.execute("SELECT category_id FROM waste_categories WHERE category_name = %s AND parent_category_id = %s", 
                                 (subcat[0], nonhazardous_parent_id))
                    result = cursor.fetchone()
                    if result:
                        nonhazardous_sub_ids[subcat[0]] = result[0]
            
            connection.commit()
            
            # Recyclable - Further subcategories
            recyclable_id = nonhazardous_sub_ids.get('Non-Hazardous - Recyclable')
            if recyclable_id:
                recyclable_subcategories = [
                    ('Recyclable - Cardboard', 'REC-CARD', 'Cardboard and paperboard', False, recyclable_id, 'kg'),
                    ('Recyclable - Clear Clean Plastics', 'REC-PLA-CL', 'Clear and clean plastic containers', False, recyclable_id, 'kg'),
                    ('Recyclable - Colored Plastics', 'REC-PLA-COL', 'Colored plastic materials', False, recyclable_id, 'kg'),
                    ('Recyclable - Glass', 'REC-GLASS', 'Glass bottles and containers', False, recyclable_id, 'kg'),
                    ('Recyclable - Metal', 'REC-MET', 'Metal scrap and containers', False, recyclable_id, 'kg'),
                    ('Recyclable - Paper', 'REC-PAP', 'Paper and office paper', False, recyclable_id, 'kg'),
                    ('Recyclable - Aluminum', 'REC-ALU', 'Aluminum cans and materials', False, recyclable_id, 'kg'),
                    ('Recyclable - Electronics', 'REC-ELEC', 'Electronic equipment for recycling', False, recyclable_id, 'kg'),
                ]
                
                for subcat in recyclable_subcategories:
                    cursor.execute("""
                        INSERT IGNORE INTO waste_categories 
                        (category_name, category_code, description, hazardous, parent_category_id, unit_of_measure)
                        VALUES (%s, %s, %s, %s, %s, %s)
                    """, subcat)
            
            connection.commit()
            
            # Compostable - Further subcategories
            compostable_id = nonhazardous_sub_ids.get('Non-Hazardous - Compostable')
            if compostable_id:
                compostable_subcategories = [
                    ('Compostable - Food Waste', 'COMP-FOOD', 'Food scraps and organic food waste', False, compostable_id, 'kg'),
                    ('Compostable - Yard Waste', 'COMP-YARD', 'Yard trimmings and garden waste', False, compostable_id, 'kg'),
                    ('Compostable - Organic Materials', 'COMP-ORG', 'Other organic compostable materials', False, compostable_id, 'kg'),
                ]
                
                for subcat in compostable_subcategories:
                    cursor.execute("""
                        INSERT IGNORE INTO waste_categories 
                        (category_name, category_code, description, hazardous, parent_category_id, unit_of_measure)
                        VALUES (%s, %s, %s, %s, %s, %s)
                    """, subcat)
            
            connection.commit()
            
            # General Waste - Further subcategories
            general_id = nonhazardous_sub_ids.get('Non-Hazardous - General Waste')
            if general_id:
                general_subcategories = [
                    ('General - Mixed Waste', 'GEN-MIX', 'Mixed general waste', False, general_id, 'kg'),
                    ('General - Construction Debris', 'GEN-CON', 'Construction and demolition debris', False, general_id, 'kg'),
                    ('General - Textiles', 'GEN-TEX', 'Textile and fabric waste', False, general_id, 'kg'),
                    ('General - Other', 'GEN-OTH', 'Other general waste', False, general_id, 'kg'),
                ]
                
                for subcat in general_subcategories:
                    cursor.execute("""
                        INSERT IGNORE INTO waste_categories 
                        (category_name, category_code, description, hazardous, parent_category_id, unit_of_measure)
                        VALUES (%s, %s, %s, %s, %s, %s)
                    """, subcat)
            
            connection.commit()
        
        # Count total categories inserted
        cursor.execute("SELECT COUNT(*) FROM waste_categories")
        total_count = cursor.fetchone()[0]
        print(f"✓ Inserted waste categories with hierarchical structure (Total: {total_count} categories)")
        
    except Error as e:
        print(f"✗ Error inserting default data: {e}")
        connection.rollback()
        import traceback
        traceback.print_exc()

if __name__ == "__main__":
    print("=" * 60)
    print("Waste Management System - Database Schema Creation")
    print("=" * 60)
    create_waste_management_schema()
    print("\n" + "=" * 60)
    print("Schema creation completed!")
    print("=" * 60)

