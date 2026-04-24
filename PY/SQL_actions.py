# File: sheener/PY/SQL_actions.py
#!/usr/bin/env python3
"""
Light-weight helper for Cursor (or any Python code) to run SQL through
the existing PHP connection layer (sheener/php/database.php).

Usage inside Cursor
-------------------
1.  Put this file anywhere on your PYTHONPATH (e.g. same folder as your notebook).
2.  Import and fire queries:

    from SQL_actions import SQL
    db = SQL()                       # uses default gateway
    rows = db.select("SELECT * FROM users WHERE active = 1")
    db.insert("users", {"name": "Ada", "email": "ada@lovelace.io"})
    db.update("users", {"active": 0}, "id = %s", [42])
    db.delete("users", "id = %s", [42])
    db.execute("CALL refresh_materialized_report()")

The gateway PHP script must be reachable at
http://localhost/gateway/sql_gateway.php (change URL below if you serve it
under a different vhost/path).

To insert test data:
    python PY/SQL_actions.py insert_test_data
    OR
    from SQL_actions import insert_test_data
    insert_test_data()
"""

import json, requests, os, urllib.parse
from typing import List, Dict, Any, Optional

_GATEWAY_URL = os.getenv("SQL_GATEWAY_URL",
                         "http://localhost/sheener/php/sql_gateway.php")

class SQL:
    """
    Tiny wrapper that POSTs SQL jobs to a PHP endpoint which in turn
    uses sheener/php/database.php (so credentials stay in one place).
    """

    def __init__(self, gateway: str = _GATEWAY_URL):
        self.gateway = gateway.rstrip("/")

    # ---------- low-level ----------------------------------------------------
    def _call(self, payload: dict) -> List[Dict[str, Any]]:
        headers = {"Content-Type": "application/json"}
        resp = requests.post(self.gateway, data=json.dumps(payload),
                             headers=headers, timeout=30)
        if resp.status_code != 200:
            raise RuntimeError(f"Gateway error {resp.status_code}: {resp.text}")
        data = resp.json()
        if data.get("error"):
            raise RuntimeError("SQL error: " + data["error"])
        return data.get("rows", [])

    # ---------- high-level ---------------------------------------------------
    def select(self, sql: str, params: Optional[list] = None) -> List[dict]:
        """Run a SELECT and return list[dict] (empty list if no rows)."""
        return self._call({"action": "select", "sql": sql, "params": params or []})

    def execute(self, sql: str, params: Optional[list] = None) -> int:
        """
        Run INSERT/UPDATE/DELETE/raw SQL.
        Returns last-insert-id (or 0 if not an AUTO_INCREMENT table).
        """
        res = self._call({"action": "execute", "sql": sql, "params": params or []})
        return res[0].get("last_id", 0) if res else 0

    # ---------- convenience CRUD --------------------------------------------
    def insert(self, table: str, row: dict) -> int:
        cols = ", ".join(f"`{k}`" for k in row)
        placeholders = ", ".join(["%s"] * len(row))
        sql = f"INSERT INTO `{table}` ({cols}) VALUES ({placeholders})"
        return self.execute(sql, list(row.values()))

    def update(self, table: str, new_values: dict, where: str,
               where_params: Optional[list] = None) -> int:
        set_clause = ", ".join(f"`{k}` = %s" for k in new_values)
        sql = f"UPDATE `{table}` SET {set_clause} WHERE {where}"
        params = list(new_values.values()) + (where_params or [])
        return self.execute(sql, params)

    def delete(self, table: str, where: str,
               where_params: Optional[list] = None) -> int:
        sql = f"DELETE FROM `{table}` WHERE {where}"
        return self.execute(sql, where_params or [])


# ---------------------------------------------------------------------------
# Insert Test Data Function
# ---------------------------------------------------------------------------
def insert_test_data():
    """
    Insert comprehensive test data for SHEEner system.
    This function populates all tables with sample data to test forms and diagrams.
    Returns a detailed execution report.
    """
    db = SQL()
    
    # Initialize report
    report = {
        'start_time': None,
        'end_time': None,
        'duration': None,
        'tables_created': [],
        'insertions': {},
        'links': {},
        'errors': [],
        'warnings': [],
        'summary': {}
    }
    
    from datetime import datetime
    report['start_time'] = datetime.now()
    
    print("="*70)
    print("SHEEner Test Data Insertion Script")
    print("="*70)
    print(f"Start Time: {report['start_time'].strftime('%Y-%m-%d %H:%M:%S')}\n")
    
    try:
        # Helper function to get count
        def get_count(table, where_clause=""):
            try:
                sql = f"SELECT COUNT(*) as cnt FROM {table}"
                if where_clause:
                    sql += f" WHERE {where_clause}"
                result = db.select(sql)
                return result[0]['cnt'] if result else 0
            except:
                return 0
        
        # Disable foreign key checks
        db.execute("SET FOREIGN_KEY_CHECKS = 0")
        print("✓ Foreign key checks disabled")
        
        # Create missing linking tables if they don't exist
        missing_tables = [
            {
                'name': 'process_map_people',
                'sql': """
                    CREATE TABLE IF NOT EXISTS process_map_people (
                        id int(11) NOT NULL AUTO_INCREMENT,
                        process_map_id int(11) NOT NULL,
                        people_id int(11) NOT NULL,
                        PRIMARY KEY (id),
                        KEY ix_process_map_id (process_map_id),
                        KEY ix_people_id (people_id),
                        CONSTRAINT process_map_people_ibfk_1 FOREIGN KEY (process_map_id) REFERENCES process_map (id) ON DELETE CASCADE,
                        CONSTRAINT process_map_people_ibfk_2 FOREIGN KEY (people_id) REFERENCES people (people_id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
                """
            },
            {
                'name': 'process_map_area',
                'sql': """
                    CREATE TABLE IF NOT EXISTS process_map_area (
                        id int(11) NOT NULL AUTO_INCREMENT,
                        process_map_id int(11) NOT NULL,
                        area_id int(11) NOT NULL,
                        PRIMARY KEY (id),
                        KEY ix_process_map_id (process_map_id),
                        KEY ix_area_id (area_id),
                        CONSTRAINT process_map_area_ibfk_1 FOREIGN KEY (process_map_id) REFERENCES process_map (id) ON DELETE CASCADE,
                        CONSTRAINT process_map_area_ibfk_2 FOREIGN KEY (area_id) REFERENCES areas (area_id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
                """
            },
            {
                'name': 'process_map_energy',
                'sql': """
                    CREATE TABLE IF NOT EXISTS process_map_energy (
                        id int(11) NOT NULL AUTO_INCREMENT,
                        process_map_id int(11) NOT NULL,
                        energy_id int(11) NOT NULL,
                        PRIMARY KEY (id),
                        KEY ix_process_map_id (process_map_id),
                        KEY ix_energy_id (energy_id),
                        CONSTRAINT process_map_energy_ibfk_1 FOREIGN KEY (process_map_id) REFERENCES process_map (id) ON DELETE CASCADE,
                        CONSTRAINT process_map_energy_ibfk_2 FOREIGN KEY (energy_id) REFERENCES energy (EnergyID) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
                """
            }
        ]
        
        for table_info in missing_tables:
            try:
                # Check if table exists before creating
                table_exists = len(db.select(f"SHOW TABLES LIKE '{table_info['name']}'")) > 0
                db.execute(table_info['sql'])
                if not table_exists:
                    report['tables_created'].append(table_info['name'])
                    print(f"✓ Created {table_info['name']} table")
                else:
                    print(f"✓ {table_info['name']} table already exists")
            except Exception as e:
                # Try to verify table exists by querying it
                try:
                    get_count(table_info['name'])
                    print(f"✓ {table_info['name']} table already exists")
                except:
                    print(f"⚠ Could not create/verify {table_info['name']} table: {e}")
        
        # Insert departments
        count_before = get_count('departments')
        db.execute("""
            INSERT IGNORE INTO departments (department_id, DepartmentName)
            VALUES
            (1, 'Quality Assurance'),
            (2, 'Manufacturing'),
            (3, 'Research & Development')
        """)
        count_after = get_count('departments')
        inserted = count_after - count_before
        report['insertions']['departments'] = {'before': count_before, 'after': count_after, 'inserted': inserted}
        if inserted > 0:
            print(f"✓ Inserted {inserted} department(s) ({count_after} total)")
        else:
            print(f"✓ Departments already exist ({count_after} total)")
        
        # Insert People
        count_before = get_count('people')
        db.execute("""
            INSERT IGNORE INTO people (people_id, FirstName, LastName, Email, Position, IsActive, department_id)
            VALUES
            (1, 'John', 'Smith', 'john.smith@amneal.com', 'Process Engineer', 1, 1),
            (2, 'Sarah', 'Johnson', 'sarah.johnson@amneal.com', 'Quality Manager', 1, 1),
            (3, 'Michael', 'Brown', 'michael.brown@amneal.com', 'Production Supervisor', 1, 2),
            (4, 'Emily', 'Davis', 'emily.davis@amneal.com', 'R&D Scientist', 1, 3),
            (5, 'David', 'Wilson', 'david.wilson@amneal.com', 'Equipment Operator', 1, 2)
        """)
        count_after = get_count('people')
        inserted = count_after - count_before
        report['insertions']['people'] = {'before': count_before, 'after': count_after, 'inserted': inserted}
        if inserted > 0:
            print(f"✓ Inserted {inserted} person/people ({count_after} total)")
        else:
            print(f"✓ People already exist ({count_after} total)")
        
        # Insert Areas
        count_before = get_count('areas')
        db.execute("""
            INSERT IGNORE INTO areas (area_id, area_name, area_type, description, location_code, is_active)
            VALUES
            (1, 'Manufacturing Floor A', 'Production', 'Main production area for solid dosage forms', 'MF-A', 1),
            (2, 'Quality Control Lab', 'Laboratory', 'QC testing and analysis laboratory', 'QC-LAB', 1),
            (3, 'Warehouse', 'Storage', 'Raw materials and finished goods storage', 'WH-01', 1),
            (4, 'R&D Laboratory', 'Laboratory', 'Research and development facility', 'R&D-LAB', 1)
        """)
        count_after = get_count('areas')
        inserted = count_after - count_before
        report['insertions']['areas'] = {'before': count_before, 'after': count_after, 'inserted': inserted}
        if inserted > 0:
            print(f"✓ Inserted {inserted} area(s) ({count_after} total)")
        else:
            print(f"✓ Areas already exist ({count_after} total)")
        
        # Insert Equipment
        count_before = get_count('equipment')
        db.execute("""
            INSERT IGNORE INTO equipment (equipment_id, item_name, equipment_type, serial_number, location, status, responsible_person_id)
            VALUES
            (1, 'Tablet Press Machine TP-101', 'Tablet Press', 'TP101-2020-001', 'Manufacturing Floor A', 'Active', 3),
            (2, 'Blender Mixer BM-205', 'Mixing Equipment', 'BM205-2019-045', 'Manufacturing Floor A', 'Active', 3),
            (3, 'HPLC System', 'Analytical Equipment', 'HPLC-2021-012', 'Quality Control Lab', 'Active', 2),
            (4, 'Coating Machine CM-150', 'Coating Equipment', 'CM150-2020-078', 'Manufacturing Floor A', 'Active', 3)
        """)
        count_after = get_count('equipment')
        inserted = count_after - count_before
        report['insertions']['equipment'] = {'before': count_before, 'after': count_after, 'inserted': inserted}
        if inserted > 0:
            print(f"✓ Inserted {inserted} equipment item(s) ({count_after} total)")
        else:
            print(f"✓ Equipment already exists ({count_after} total)")
        
        # Insert Materials
        count_before = get_count('materials')
        db.execute("""
            INSERT IGNORE INTO materials (MaterialID, MaterialName, MaterialType, Description, UnitOfMeasure)
            VALUES
            (1, 'Lactose Monohydrate', 'Excipient', 'Primary excipient for tablet formulation', 'kg'),
            (2, 'API Compound A', 'Active Ingredient', 'Active pharmaceutical ingredient', 'g'),
            (3, 'Magnesium Stearate', 'Lubricant', 'Tablet lubricant', 'g'),
            (4, 'Microcrystalline Cellulose', 'Excipient', 'Binder and filler', 'kg')
        """)
        count_after = get_count('materials')
        inserted = count_after - count_before
        report['insertions']['materials'] = {'before': count_before, 'after': count_after, 'inserted': inserted}
        if inserted > 0:
            print(f"✓ Inserted {inserted} material(s) ({count_after} total)")
        else:
            print(f"✓ Materials already exist ({count_after} total)")
        
        # Insert Energy Types and Energy
        count_before = get_count('energytype')
        db.execute("""
            INSERT IGNORE INTO energytype (EnergyTypeID, EnergyTypeName, Description)
            VALUES 
            (1, 'Electrical', 'Electrical energy'),
            (2, 'Compressed Air', 'Compressed air systems'),
            (3, 'Steam', 'Steam generation systems')
        """)
        count_after = get_count('energytype')
        inserted = count_after - count_before
        report['insertions']['energytype'] = {'before': count_before, 'after': count_after, 'inserted': inserted}
        if inserted > 0:
            print(f"✓ Inserted {inserted} energy type(s) ({count_after} total)")
        else:
            print(f"✓ Energy types already exist ({count_after} total)")
        
        count_before = get_count('energy')
        db.execute("""
            INSERT IGNORE INTO energy (EnergyID, EnergyName, EnergyTypeID, Description)
            VALUES
            (1, 'Main Electrical Supply', 1, 'Primary electrical power for manufacturing'),
            (2, 'Compressed Air System', 2, 'Compressed air for pneumatic equipment'),
            (3, 'Steam Generation', 3, 'Steam for heating and sterilization')
        """)
        count_after = get_count('energy')
        inserted = count_after - count_before
        report['insertions']['energy'] = {'before': count_before, 'after': count_after, 'inserted': inserted}
        if inserted > 0:
            print(f"✓ Inserted {inserted} energy item(s) ({count_after} total)")
        else:
            print(f"✓ Energy already exists ({count_after} total)")
        
        # Insert Process Map Hierarchy
        count_before = get_count('process_map')
        # Processes
        db.execute("""
            INSERT IGNORE INTO process_map (id, type, text, parent) VALUES
            (1, 'process', 'Research and Development', NULL),
            (2, 'process', 'Formulation Development', NULL),
            (3, 'process', 'Manufacturing Process', NULL),
            (4, 'process', 'Quality Control', NULL)
        """)
        # Steps
        db.execute("""
            INSERT IGNORE INTO process_map (id, type, text, parent) VALUES
            (5, 'step', 'Device design and prototyping', 1),
            (6, 'step', 'API selection and characterization', 2),
            (7, 'step', 'Excipient selection (e.g., lactose for DPIs)', 2),
            (8, 'step', 'Granulation', 3),
            (9, 'step', 'Tablet Compression', 3),
            (10, 'step', 'Coating', 3),
            (11, 'step', 'Packaging', 3),
            (12, 'step', 'In-process Testing', 4),
            (13, 'step', 'Final Product Testing', 4)
        """)
        # Substeps
        db.execute("""
            INSERT IGNORE INTO process_map (id, type, text, parent) VALUES
            (14, 'substep', 'CAD modelling', 5),
            (15, 'substep', 'Prototype fabrication', 5),
            (16, 'substep', 'API purity analysis', 6),
            (17, 'substep', 'Particle size distribution', 6),
            (18, 'substep', 'Compatibility testing', 7),
            (19, 'substep', 'Wet granulation', 8),
            (20, 'substep', 'Drying', 8),
            (21, 'substep', 'Milling', 8),
            (22, 'substep', 'Blending', 9),
            (23, 'substep', 'Compression', 9),
            (24, 'substep', 'Film coating', 10),
            (25, 'substep', 'Primary packaging', 11),
            (26, 'substep', 'Secondary packaging', 11)
        """)
        # Tasks
        db.execute("""
            INSERT IGNORE INTO process_map (id, type, text, parent) VALUES
            (27, 'task', 'Design device specifications', 14),
            (28, 'task', 'Create 3D CAD model', 14),
            (29, 'task', 'Fabricate prototype device', 15),
            (30, 'task', 'Test API purity using HPLC', 16),
            (31, 'task', 'Measure particle size using laser diffraction', 17),
            (32, 'task', 'Perform excipient compatibility study', 18),
            (33, 'task', 'Mix API with granulating solution', 19),
            (34, 'task', 'Dry granules in fluid bed dryer', 20),
            (35, 'task', 'Mill dried granules', 21),
            (36, 'task', 'Blend granules with lubricant', 22),
            (37, 'task', 'Compress tablets using tablet press', 23),
            (38, 'task', 'Apply film coating solution', 24),
            (39, 'task', 'Package tablets in blisters', 25),
            (40, 'task', 'Label and carton packaging', 26)
        """)
        count_after = get_count('process_map')
        inserted = count_after - count_before
        report['insertions']['process_map'] = {'before': count_before, 'after': count_after, 'inserted': inserted}
        if inserted > 0:
            print(f"✓ Inserted {inserted} process map item(s) ({count_after} total)")
        else:
            print(f"✓ Process map items already exist ({count_after} total)")
        
        # Link People to Process Steps
        count_before = get_count('process_map_people')
        db.execute("""
            INSERT IGNORE INTO process_map_people (process_map_id, people_id) VALUES
            (5, 4), (6, 4), (7, 4), (8, 1), (9, 1), (10, 1), (11, 3), (12, 2), (13, 2)
        """)
        count_after = get_count('process_map_people')
        inserted = count_after - count_before
        report['links']['process_map_people'] = {'before': count_before, 'after': count_after, 'inserted': inserted}
        if inserted > 0:
            print(f"✓ Linked {inserted} people to process map ({count_after} total links)")
        else:
            print(f"✓ People links already exist ({count_after} total links)")
        
        # Link Equipment
        count_before = get_count('process_map_equipment')
        db.execute("""
            INSERT IGNORE INTO process_map_equipment (process_map_id, equipment_id) VALUES
            (8, 2), (9, 1), (10, 4), (12, 3), (13, 3)
        """)
        count_after = get_count('process_map_equipment')
        inserted = count_after - count_before
        report['links']['process_map_equipment'] = {'before': count_before, 'after': count_after, 'inserted': inserted}
        if inserted > 0:
            print(f"✓ Linked {inserted} equipment to process map ({count_after} total links)")
        else:
            print(f"✓ Equipment links already exist ({count_after} total links)")
        
        # Link Areas
        count_before = get_count('process_map_area')
        db.execute("""
            INSERT IGNORE INTO process_map_area (process_map_id, area_id) VALUES
            (1, 4), (2, 4), (3, 1), (4, 2), (8, 1), (9, 1), (10, 1), (11, 1), (12, 2), (13, 2)
        """)
        count_after = get_count('process_map_area')
        inserted = count_after - count_before
        report['links']['process_map_area'] = {'before': count_before, 'after': count_after, 'inserted': inserted}
        if inserted > 0:
            print(f"✓ Linked {inserted} areas to process map ({count_after} total links)")
        else:
            print(f"✓ Area links already exist ({count_after} total links)")
        
        # Link Materials
        count_before = get_count('process_map_material')
        db.execute("""
            INSERT IGNORE INTO process_map_material (process_map_id, material_id, quantity, measurement_unit_id) VALUES
            (7, 1, 50.0000, NULL), (8, 1, 100.0000, NULL), (8, 4, 50.0000, NULL),
            (9, 2, 10.0000, NULL), (9, 3, 1.0000, NULL), (10, 1, 5.0000, NULL)
        """)
        count_after = get_count('process_map_material')
        inserted = count_after - count_before
        report['links']['process_map_material'] = {'before': count_before, 'after': count_after, 'inserted': inserted}
        if inserted > 0:
            print(f"✓ Linked {inserted} materials to process map ({count_after} total links)")
        else:
            print(f"✓ Material links already exist ({count_after} total links)")
        
        # Link Energy
        count_before = get_count('process_map_energy')
        db.execute("""
            INSERT IGNORE INTO process_map_energy (process_map_id, energy_id) VALUES
            (8, 1), (8, 3), (9, 1), (9, 2), (10, 1), (10, 3), (12, 1), (13, 1)
        """)
        count_after = get_count('process_map_energy')
        inserted = count_after - count_before
        report['links']['process_map_energy'] = {'before': count_before, 'after': count_after, 'inserted': inserted}
        if inserted > 0:
            print(f"✓ Linked {inserted} energy to process map ({count_after} total links)")
        else:
            print(f"✓ Energy links already exist ({count_after} total links)")
        
        # Create process_definitions table if it doesn't exist
        try:
            table_exists = len(db.select("SHOW TABLES LIKE 'process_definitions'")) > 0
            db.execute("""
                CREATE TABLE IF NOT EXISTS process_definitions (
                    process_id int(11) NOT NULL AUTO_INCREMENT,
                    process_family_id int(11) DEFAULT NULL,
                    code varchar(50) DEFAULT NULL,
                    name varchar(100) DEFAULT NULL,
                    version varchar(20) DEFAULT NULL,
                    status varchar(20) DEFAULT NULL,
                    effective_from date DEFAULT NULL,
                    effective_to date DEFAULT NULL,
                    governing_document_id int(11) DEFAULT NULL,
                    PRIMARY KEY (process_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            """)
            if not table_exists:
                report['tables_created'].append('process_definitions')
                print("✓ Created process_definitions table")
            else:
                print("✓ process_definitions table already exists")
        except Exception as e:
            try:
                get_count('process_definitions')
                print("✓ process_definitions table already exists")
            except:
                print(f"⚠ Could not create/verify process_definitions table: {e}")
        
        # Create process_steps table if it doesn't exist
        try:
            table_exists = len(db.select("SHOW TABLES LIKE 'process_steps'")) > 0
            db.execute("""
                CREATE TABLE IF NOT EXISTS process_steps (
                    step_id int(11) NOT NULL AUTO_INCREMENT,
                    process_id int(11) NOT NULL,
                    step_order int(11) NOT NULL,
                    name varchar(255) NOT NULL,
                    description text DEFAULT NULL,
                    mandatory tinyint(1) DEFAULT 1,
                    can_be_parallel tinyint(1) DEFAULT 0,
                    PRIMARY KEY (step_id),
                    KEY ix_process_id (process_id),
                    CONSTRAINT process_steps_ibfk_1 FOREIGN KEY (process_id) REFERENCES process_definitions (process_id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            """)
            if not table_exists:
                report['tables_created'].append('process_steps')
                print("✓ Created process_steps table")
            else:
                print("✓ process_steps table already exists")
        except Exception as e:
            try:
                get_count('process_steps')
                print("✓ process_steps table already exists")
            except:
                print(f"⚠ Could not create/verify process_steps table: {e}")
        
        # Insert Process Definitions
        count_before = get_count('process_definitions')
        db.execute("""
            INSERT IGNORE INTO process_definitions (process_id, code, name, version, status, effective_from, effective_to)
            VALUES
            (1, 'PROC-RD-001', 'Research and Development Process', '1.0', 'Active', '2024-01-01', NULL),
            (2, 'PROC-FD-001', 'Formulation Development Process', '1.0', 'Active', '2024-01-01', NULL),
            (3, 'PROC-MFG-001', 'Manufacturing Process', '2.0', 'Active', '2024-01-15', NULL),
            (4, 'PROC-QC-001', 'Quality Control Process', '1.0', 'Active', '2024-01-01', NULL)
        """)
        count_after = get_count('process_definitions')
        inserted = count_after - count_before
        report['insertions']['process_definitions'] = {'before': count_before, 'after': count_after, 'inserted': inserted}
        if inserted > 0:
            print(f"✓ Inserted {inserted} process definition(s) ({count_after} total)")
        else:
            print(f"✓ Process definitions already exist ({count_after} total)")
        
        # Insert Process Steps
        count_before = get_count('process_steps')
        db.execute("""
            INSERT IGNORE INTO process_steps (step_id, process_id, step_order, name, description, mandatory, can_be_parallel)
            VALUES
            (1, 1, 1, 'Device Design and Prototyping', 'Design and prototype development for new devices', 1, 0),
            (2, 2, 1, 'API Selection and Characterization', 'Select and characterize active pharmaceutical ingredients', 1, 0),
            (3, 2, 2, 'Excipient Selection', 'Select appropriate excipients for formulation', 1, 0),
            (4, 3, 1, 'Granulation', 'Wet granulation process for tablet manufacturing', 1, 0),
            (5, 3, 2, 'Tablet Compression', 'Compression of granules into tablets', 1, 0),
            (6, 3, 3, 'Coating', 'Film coating of compressed tablets', 0, 0),
            (7, 3, 4, 'Packaging', 'Primary and secondary packaging of finished products', 1, 0),
            (8, 4, 1, 'In-process Testing', 'Quality testing during manufacturing process', 1, 0),
            (9, 4, 2, 'Final Product Testing', 'Final quality control testing of finished products', 1, 0)
        """)
        count_after = get_count('process_steps')
        inserted = count_after - count_before
        report['insertions']['process_steps'] = {'before': count_before, 'after': count_after, 'inserted': inserted}
        if inserted > 0:
            print(f"✓ Inserted {inserted} process step(s) ({count_after} total)")
        else:
            print(f"✓ Process steps already exist ({count_after} total)")
        
        # Insert Tasks
        # Note: Using the actual tasks table structure (task_name, task_description, etc.)
        count_before = get_count('tasks')
        db.execute("""
            INSERT IGNORE INTO tasks (task_id, task_name, task_description, task_type, priority, due_date, status, assigned_to, department_id)
            VALUES
            (1, 'Design Device Specifications', 'Create detailed specifications for new device design', 'Project Task', 'High', DATE_ADD(NOW(), INTERVAL 7 DAY), 'Not Started', 4, 3),
            (2, 'Create 3D CAD Model', 'Develop 3D CAD model based on specifications', 'Project Task', 'High', DATE_ADD(NOW(), INTERVAL 10 DAY), 'In Progress', 4, 3),
            (3, 'Test API Purity', 'Perform HPLC analysis to verify API purity', 'Compliance Task', 'High', DATE_ADD(NOW(), INTERVAL 3 DAY), 'Not Started', 4, 3),
            (4, 'Measure Particle Size', 'Determine particle size distribution of API', 'Compliance Task', 'Medium', DATE_ADD(NOW(), INTERVAL 5 DAY), 'Not Started', 4, 3),
            (5, 'Excipient Compatibility Study', 'Test compatibility of excipients with API', 'Compliance Task', 'High', DATE_ADD(NOW(), INTERVAL 7 DAY), 'Not Started', 4, 3),
            (6, 'Perform Granulation', 'Execute wet granulation process', 'Operational Task', 'High', DATE_ADD(NOW(), INTERVAL 1 DAY), 'Not Started', 1, 2),
            (7, 'Compress Tablets', 'Compress granules into tablets using tablet press', 'Operational Task', 'High', DATE_ADD(NOW(), INTERVAL 2 DAY), 'Not Started', 1, 2),
            (8, 'Apply Film Coating', 'Apply film coating to compressed tablets', 'Operational Task', 'Medium', DATE_ADD(NOW(), INTERVAL 3 DAY), 'Not Started', 1, 2),
            (9, 'Package Tablets', 'Package finished tablets in primary and secondary packaging', 'Operational Task', 'High', DATE_ADD(NOW(), INTERVAL 4 DAY), 'Not Started', 3, 2),
            (10, 'In-process Quality Testing', 'Perform quality tests during manufacturing', 'Compliance Task', 'High', DATE_ADD(NOW(), INTERVAL 2 DAY), 'Not Started', 2, 1),
            (11, 'Final Product Release Testing', 'Complete final quality control testing', 'Compliance Task', 'High', DATE_ADD(NOW(), INTERVAL 5 DAY), 'Not Started', 2, 1)
        """)
        count_after = get_count('tasks')
        inserted = count_after - count_before
        report['insertions']['tasks'] = {'before': count_before, 'after': count_after, 'inserted': inserted}
        if inserted > 0:
            print(f"✓ Inserted {inserted} task(s) ({count_after} total)")
        else:
            print(f"✓ Tasks already exist ({count_after} total)")
        
        # Insert Events
        count_before = get_count('events')
        db.execute("""
            INSERT IGNORE INTO events (event_id, event_type, reported_by, reported_date, description, status, event_subcategory, likelihood, severity, risk_rating, department_id)
            VALUES
            (1, 'OFI', 1, NOW(), 'Opportunity to improve granulation process efficiency', 'Open', 'Process Improvement', 3, 2, 6, 2),
            (2, 'Adverse Event', 2, DATE_SUB(NOW(), INTERVAL 2 DAY), 'Tablet hardness variation detected in batch', 'Under Investigation', 'Quality Issue', 4, 3, 12, 1),
            (3, 'Defects', 3, DATE_SUB(NOW(), INTERVAL 5 DAY), 'Packaging material defect identified', 'Assessed', 'Material Defect', 2, 2, 4, 2),
            (4, 'NonCompliance', 2, DATE_SUB(NOW(), INTERVAL 1 DAY), 'Deviation from standard operating procedure', 'Change Control Requested', 'SOP Deviation', 3, 4, 12, 1)
        """)
        count_after = get_count('events')
        inserted = count_after - count_before
        report['insertions']['events'] = {'before': count_before, 'after': count_after, 'inserted': inserted}
        if inserted > 0:
            print(f"✓ Inserted {inserted} event(s) ({count_after} total)")
        else:
            print(f"✓ Events already exist ({count_after} total)")
        
        # Update AUTO_INCREMENT values
        db.execute("ALTER TABLE process_map AUTO_INCREMENT = 100")
        db.execute("ALTER TABLE process_definitions AUTO_INCREMENT = 100")
        db.execute("ALTER TABLE process_steps AUTO_INCREMENT = 100")
        db.execute("ALTER TABLE tasks AUTO_INCREMENT = 100")
        db.execute("ALTER TABLE events AUTO_INCREMENT = 100")
        print("✓ Updated auto-increment values")
        
        # Re-enable foreign key checks
        db.execute("SET FOREIGN_KEY_CHECKS = 1")
        print("✓ Foreign key checks re-enabled")
        
        # Generate comprehensive execution report
        report['end_time'] = datetime.now()
        report['duration'] = (report['end_time'] - report['start_time']).total_seconds()
        
        # Get final counts (using get_count function defined at top of try block)
        # Final counts
        final_counts = {
            'departments': get_count('departments'),
            'people': get_count('people'),
            'areas': get_count('areas'),
            'equipment': get_count('equipment'),
            'materials': get_count('materials'),
            'energytype': get_count('energytype'),
            'energy': get_count('energy'),
            'process_map': get_count('process_map'),
            'process_definitions': get_count('process_definitions'),
            'process_steps': get_count('process_steps'),
            'tasks': get_count('tasks'),
            'events': get_count('events'),
            'process_map_people': get_count('process_map_people'),
            'process_map_equipment': get_count('process_map_equipment'),
            'process_map_area': get_count('process_map_area'),
            'process_map_material': get_count('process_map_material'),
            'process_map_energy': get_count('process_map_energy')
        }
        
        # Expected counts (what we tried to insert)
        expected_counts = {
            'departments': 3,
            'people': 5,
            'areas': 4,
            'equipment': 4,
            'materials': 4,
            'energytype': 3,
            'energy': 3,
            'process_map': 40,  # 4 processes + 9 steps + 13 substeps + 14 tasks
            'process_definitions': 4,
            'process_steps': 9,
            'tasks': 11,
            'events': 4,
            'process_map_people': 9,
            'process_map_equipment': 5,
            'process_map_area': 10,
            'process_map_material': 6,
            'process_map_energy': 8
        }
        
        # Calculate statistics
        report['summary'] = {
            'total_tables_processed': len(final_counts),
            'total_records_after': sum(final_counts.values()),
            'expected_records': sum(expected_counts.values()),
            'tables_with_data': sum(1 for k, v in final_counts.items() if v > 0)
        }
        
        # Print detailed execution report
        print("\n" + "="*70)
        print("EXECUTION REPORT")
        print("="*70)
        print(f"End Time: {report['end_time'].strftime('%Y-%m-%d %H:%M:%S')}")
        print(f"Duration: {report['duration']:.2f} seconds")
        print(f"\nTables Created: {len(report['tables_created'])}")
        if report['tables_created']:
            for table in report['tables_created']:
                print(f"  ✓ {table}")
        
        print("\n" + "-"*70)
        print("DATA INSERTION SUMMARY")
        print("-"*70)
        print(f"{'Table':<30} {'Inserted':<12} {'Before':<12} {'After':<12} {'Status':<15}")
        print("-"*70)
        
        main_tables = ['departments', 'people', 'areas', 'equipment', 'materials', 
                      'energytype', 'energy', 'process_map', 'process_definitions', 
                      'process_steps', 'tasks', 'events']
        
        for table in main_tables:
            if table in report['insertions']:
                ins_data = report['insertions'][table]
                inserted = ins_data['inserted']
                before = ins_data['before']
                after = ins_data['after']
                status = "✓ New" if inserted > 0 else "○ Existed"
                print(f"{table:<30} {inserted:<12} {before:<12} {after:<12} {status:<15}")
            else:
                # Fallback for tables not tracked
                expected = expected_counts.get(table, 0)
                current = final_counts.get(table, 0)
                status = "✓ Complete" if current >= expected else f"⚠ Partial"
                print(f"{table:<30} {'N/A':<12} {'N/A':<12} {current:<12} {status:<15}")
        
        print("\n" + "-"*70)
        print("LINKING TABLES SUMMARY")
        print("-"*70)
        print(f"{'Link Table':<30} {'Inserted':<12} {'Before':<12} {'After':<12} {'Status':<15}")
        print("-"*70)
        linking_tables = ['process_map_people', 'process_map_equipment', 'process_map_area', 
                         'process_map_material', 'process_map_energy']
        for table in linking_tables:
            if table in report['links']:
                link_data = report['links'][table]
                inserted = link_data['inserted']
                before = link_data['before']
                after = link_data['after']
                status = "✓ New" if inserted > 0 else "○ Existed"
                print(f"{table:<30} {inserted:<12} {before:<12} {after:<12} {status:<15}")
            else:
                # Fallback
                count = final_counts.get(table, 0)
                expected = expected_counts.get(table, 0)
                status = "✓ Complete" if count >= expected else f"⚠ Partial"
                print(f"{table:<30} {'N/A':<12} {'N/A':<12} {count:<12} {status:<15}")
        
        print("\n" + "-"*70)
        print("PROCESS MAP HIERARCHY")
        print("-"*70)
        process_map_by_type = db.select("""
            SELECT type, COUNT(*) as cnt 
            FROM process_map 
            GROUP BY type 
            ORDER BY 
                CASE type 
                    WHEN 'process' THEN 1 
                    WHEN 'step' THEN 2 
                    WHEN 'substep' THEN 3 
                    WHEN 'task' THEN 4 
                    ELSE 5 
                END
        """)
        for row in process_map_by_type:
            print(f"  {row['type'].capitalize():<15} {row['cnt']:>5} items")
        
        print("\n" + "-"*70)
        print("OVERALL STATISTICS")
        print("-"*70)
        print(f"  Total Tables Processed:     {report['summary']['total_tables_processed']}")
        print(f"  Tables with Data:           {report['summary']['tables_with_data']}")
        print(f"  Total Records in Database:  {report['summary']['total_records_after']}")
        print(f"  Expected Records:           {report['summary']['expected_records']}")
        
        if report['warnings']:
            print("\n" + "-"*70)
            print("WARNINGS")
            print("-"*70)
            for warning in report['warnings']:
                print(f"  ⚠ {warning}")
        
        print("\n" + "="*70)
        print("✓ Test Data Insertion Completed Successfully!")
        print("="*70)
        
        # Return report for programmatic access
        report['success'] = True
        report['final_counts'] = final_counts
        report['expected_counts'] = expected_counts
        return report
        
    except Exception as e:
        # Re-enable foreign key checks even on error
        try:
            db.execute("SET FOREIGN_KEY_CHECKS = 1")
        except:
            pass
        
        report['end_time'] = datetime.now()
        report['duration'] = (report['end_time'] - report['start_time']).total_seconds()
        report['errors'].append(str(e))
        
        print("\n" + "="*70)
        print("✗ ERROR OCCURRED")
        print("="*70)
        print(f"Error: {e}")
        print(f"Duration: {report['duration']:.2f} seconds")
        print("\nPartial execution report:")
        print(f"  Tables Created: {len(report['tables_created'])}")
        if report['tables_created']:
            for table in report['tables_created']:
                print(f"    ✓ {table}")
        
        report['success'] = False
        return report


# ---------------------------------------------------------------------------
# Quick sanity test when run directly:  python SQL_actions.py
# ---------------------------------------------------------------------------
if __name__ == "__main__":
    import sys
    
    if len(sys.argv) > 1 and sys.argv[1] == "insert_test_data":
        # Run test data insertion
        insert_test_data()
    else:
        # Default: quick sanity test
        db = SQL()
        rows = db.select("SELECT * FROM events LIMIT 5")
        print(f"Found {len(rows)} events (showing first 5):")
        for row in rows:
            print(row)