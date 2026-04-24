# File: sheener/PY/pharmaceutical_manufacturing_flows.py
#!/usr/bin/env python3
"""
Pharmaceutical Manufacturing Process Flows - MDI and DPI
Models complete manufacturing processes from procurement to shipment including waste streams

Usage:
    python PY/pharmaceutical_manufacturing_flows.py
    OR
    from pharmaceutical_manufacturing_flows import insert_mdi_flow, insert_dpi_flow
    insert_mdi_flow()
    insert_dpi_flow()
"""

import sys
import os
from datetime import datetime, timedelta

# Add parent directory to path to import SQL_actions
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from PY.SQL_actions import SQL

def get_count(db, table, where_clause=""):
    """Helper function to get count from table"""
    try:
        sql = f"SELECT COUNT(*) as cnt FROM `{table}`"
        if where_clause:
            sql += f" WHERE {where_clause}"
        result = db.select(sql)
        return result[0]['cnt'] if result else 0
    except:
        return 0

def insert_mdi_manufacturing_flow():
    """
    Insert complete MDI (Metered Dose Inhaler) manufacturing process flow
    From procurement through to finished product shipment, including waste streams
    """
    db = SQL()
    
    print("="*80)
    print("MDI (Metered Dose Inhaler) Manufacturing Process Flow")
    print("="*80)
    print(f"Start Time: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
    
    report = {
        'process_map_inserted': 0,
        'process_definitions_inserted': 0,
        'process_steps_inserted': 0,
        'tasks_inserted': 0,
        'waste_records_inserted': 0,
        '7ps_links_created': 0,
        'errors': []
    }
    
    try:
        db.execute("SET FOREIGN_KEY_CHECKS = 0")
        
        # ===================================================================
        # 1. CREATE PROCESS MAP HIERARCHY FOR MDI MANUFACTURING
        # ===================================================================
        print("Creating MDI Process Map Hierarchy...")
        
        # Main Process: MDI Manufacturing
        mdi_process_id = db.insert("process_map", {
            "type": "process",
            "text": "MDI (Metered Dose Inhaler) Manufacturing",
            "parent": None
        })
        report['process_map_inserted'] += 1
        print(f"  ✓ Created MDI Manufacturing Process (ID: {mdi_process_id})")
        
        # Stage 1: Procurement
        proc_stage_id = db.insert("process_map", {
            "type": "step",
            "text": "1. Procurement & Raw Material Management",
            "parent": mdi_process_id
        })
        report['process_map_inserted'] += 1
        
        proc_substeps = [
            ("1.1", "Supplier Qualification & Selection", proc_stage_id),
            ("1.2", "Purchase Order Processing", proc_stage_id),
            ("1.3", "Incoming Material Receipt", proc_stage_id),
            ("1.4", "Incoming Material Quality Control", proc_stage_id),
            ("1.5", "Raw Material Storage & Inventory Management", proc_stage_id)
        ]
        
        proc_substep_ids = {}
        for code, name, parent in proc_substeps:
            substep_id = db.insert("process_map", {
                "type": "substep",
                "text": f"{code} {name}",
                "parent": parent
            })
            proc_substep_ids[code] = substep_id
            report['process_map_inserted'] += 1
        
        # Stage 2: API Processing
        api_stage_id = db.insert("process_map", {
            "type": "step",
            "text": "2. API (Active Pharmaceutical Ingredient) Processing",
            "parent": mdi_process_id
        })
        report['process_map_inserted'] += 1
        
        api_substeps = [
            ("2.1", "API Receipt & Verification", api_stage_id),
            ("2.2", "API Micronization (if required)", api_stage_id),
            ("2.3", "Particle Size Distribution Analysis", api_stage_id),
            ("2.4", "API Storage (Controlled Conditions)", api_stage_id)
        ]
        
        api_substep_ids = {}
        for code, name, parent in api_substeps:
            substep_id = db.insert("process_map", {
                "type": "substep",
                "text": f"{code} {name}",
                "parent": parent
            })
            api_substep_ids[code] = substep_id
            report['process_map_inserted'] += 1
        
        # Stage 3: Formulation Preparation (MDI Specific)
        form_stage_id = db.insert("process_map", {
            "type": "step",
            "text": "3. MDI Formulation Preparation",
            "parent": mdi_process_id
        })
        report['process_map_inserted'] += 1
        
        form_substeps = [
            ("3.1", "Preparation of Drug Suspension/Solution", form_stage_id),
            ("3.2", "Mixing with Propellant (HFA-134a or HFA-227)", form_stage_id),
            ("3.3", "Homogenization & Suspension Stability", form_stage_id),
            ("3.4", "In-process Quality Testing", form_stage_id)
        ]
        
        form_substep_ids = {}
        for code, name, parent in form_substeps:
            substep_id = db.insert("process_map", {
                "type": "substep",
                "text": f"{code} {name}",
                "parent": parent
            })
            form_substep_ids[code] = substep_id
            report['process_map_inserted'] += 1
        
        # Stage 4: Device Components Manufacturing
        device_stage_id = db.insert("process_map", {
            "type": "step",
            "text": "4. Device Components Manufacturing",
            "parent": mdi_process_id
        })
        report['process_map_inserted'] += 1
        
        device_substeps = [
            ("4.1", "Injection Molding of Plastic Components", device_stage_id),
            ("4.2", "Metal Canister Fabrication", device_stage_id),
            ("4.3", "Valve Assembly", device_stage_id),
            ("4.4", "Actuator Manufacturing", device_stage_id),
            ("4.5", "Component Quality Inspection", device_stage_id)
        ]
        
        device_substep_ids = {}
        for code, name, parent in device_substeps:
            substep_id = db.insert("process_map", {
                "type": "substep",
                "text": f"{code} {name}",
                "parent": parent
            })
            device_substep_ids[code] = substep_id
            report['process_map_inserted'] += 1
        
        # Stage 5: Filling & Assembly (MDI Specific)
        fill_stage_id = db.insert("process_map", {
            "type": "step",
            "text": "5. MDI Filling & Assembly",
            "parent": mdi_process_id
        })
        report['process_map_inserted'] += 1
        
        fill_substeps = [
            ("5.1", "Canister Cleaning & Preparation", fill_stage_id),
            ("5.2", "Canister Filling with Drug Formulation", fill_stage_id),
            ("5.3", "Propellant Charging", fill_stage_id),
            ("5.4", "Valve Crimping", fill_stage_id),
            ("5.5", "Actuator Attachment", fill_stage_id),
            ("5.6", "Leak Testing", fill_stage_id),
            ("5.7", "Dose Uniformity Testing", fill_stage_id)
        ]
        
        fill_substep_ids = {}
        for code, name, parent in fill_substeps:
            substep_id = db.insert("process_map", {
                "type": "substep",
                "text": f"{code} {name}",
                "parent": parent
            })
            fill_substep_ids[code] = substep_id
            report['process_map_inserted'] += 1
        
        # Stage 6: In-Process Quality Control
        ipqc_stage_id = db.insert("process_map", {
            "type": "step",
            "text": "6. In-Process Quality Control",
            "parent": mdi_process_id
        })
        report['process_map_inserted'] += 1
        
        ipqc_substeps = [
            ("6.1", "Weight Checks", ipqc_stage_id),
            ("6.2", "Leak Rate Testing", ipqc_stage_id),
            ("6.3", "Dose Uniformity Testing", ipqc_stage_id),
            ("6.4", "Aerodynamic Particle Size Distribution (APSD)", ipqc_stage_id),
            ("6.5", "Spray Pattern Analysis", ipqc_stage_id)
        ]
        
        ipqc_substep_ids = {}
        for code, name, parent in ipqc_substeps:
            substep_id = db.insert("process_map", {
                "type": "substep",
                "text": f"{code} {name}",
                "parent": parent
            })
            ipqc_substep_ids[code] = substep_id
            report['process_map_inserted'] += 1
        
        # Stage 7: Packaging
        pack_stage_id = db.insert("process_map", {
            "type": "step",
            "text": "7. Packaging",
            "parent": mdi_process_id
        })
        report['process_map_inserted'] += 1
        
        pack_substeps = [
            ("7.1", "Primary Packaging (Canister Sealing)", pack_stage_id),
            ("7.2", "Secondary Packaging (Carton Boxing)", pack_stage_id),
            ("7.3", "Patient Information Leaflet Insertion", pack_stage_id),
            ("7.4", "Batch Number & Expiry Date Labeling", pack_stage_id)
        ]
        
        pack_substep_ids = {}
        for code, name, parent in pack_substeps:
            substep_id = db.insert("process_map", {
                "type": "substep",
                "text": f"{code} {name}",
                "parent": parent
            })
            pack_substep_ids[code] = substep_id
            report['process_map_inserted'] += 1
        
        # Stage 8: Quality Assurance Testing
        qa_stage_id = db.insert("process_map", {
            "type": "step",
            "text": "8. Quality Assurance Testing",
            "parent": mdi_process_id
        })
        report['process_map_inserted'] += 1
        
        qa_substeps = [
            ("8.1", "Visual Inspection", qa_stage_id),
            ("8.2", "Aerodynamic Particle Size Distribution (APSD)", qa_stage_id),
            ("8.3", "Delivered Dose Uniformity", qa_stage_id),
            ("8.4", "Leak Rate Testing", qa_stage_id),
            ("8.5", "Spray Pattern & Plume Geometry", qa_stage_id),
            ("8.6", "Batch Release Testing", qa_stage_id)
        ]
        
        qa_substep_ids = {}
        for code, name, parent in qa_substeps:
            substep_id = db.insert("process_map", {
                "type": "substep",
                "text": f"{code} {name}",
                "parent": parent
            })
            qa_substep_ids[code] = substep_id
            report['process_map_inserted'] += 1
        
        # Stage 9: Sterilization (if required)
        ster_stage_id = db.insert("process_map", {
            "type": "step",
            "text": "9. Sterilization (if required)",
            "parent": mdi_process_id
        })
        report['process_map_inserted'] += 1
        
        ster_substeps = [
            ("9.1", "Ethylene Oxide (EO) Sterilization", ster_stage_id),
            ("9.2", "Bioburden Testing", ster_stage_id),
            ("9.3", "Residual EO Testing", ster_stage_id),
            ("9.4", "Sterility Testing", ster_stage_id)
        ]
        
        ster_substep_ids = {}
        for code, name, parent in ster_substeps:
            substep_id = db.insert("process_map", {
                "type": "substep",
                "text": f"{code} {name}",
                "parent": parent
            })
            ster_substep_ids[code] = substep_id
            report['process_map_inserted'] += 1
        
        # Stage 10: Warehousing & Distribution
        wh_stage_id = db.insert("process_map", {
            "type": "step",
            "text": "10. Warehousing & Distribution",
            "parent": mdi_process_id
        })
        report['process_map_inserted'] += 1
        
        wh_substeps = [
            ("10.1", "Storage under Controlled Conditions", wh_stage_id),
            ("10.2", "Shipment Preparation", wh_stage_id),
            ("10.3", "Distribution to Wholesalers/Pharmacies", wh_stage_id),
            ("10.4", "Cold Chain Management (if required)", wh_stage_id)
        ]
        
        wh_substep_ids = {}
        for code, name, parent in wh_substeps:
            substep_id = db.insert("process_map", {
                "type": "substep",
                "text": f"{code} {name}",
                "parent": parent
            })
            wh_substep_ids[code] = substep_id
            report['process_map_inserted'] += 1
        
        # Stage 11: Waste Stream Management
        waste_stage_id = db.insert("process_map", {
            "type": "step",
            "text": "11. Waste Stream Management",
            "parent": mdi_process_id
        })
        report['process_map_inserted'] += 1
        
        waste_substeps = [
            ("11.1", "Waste Identification & Classification", waste_stage_id),
            ("11.2", "Waste Segregation", waste_stage_id),
            ("11.3", "Waste Collection & Storage", waste_stage_id),
            ("11.4", "Waste Treatment (if applicable)", waste_stage_id),
            ("11.5", "Waste Disposal", waste_stage_id),
            ("11.6", "Waste Documentation & Reporting", waste_stage_id)
        ]
        
        waste_substep_ids = {}
        for code, name, parent in waste_substeps:
            substep_id = db.insert("process_map", {
                "type": "substep",
                "text": f"{code} {name}",
                "parent": parent
            })
            waste_substep_ids[code] = substep_id
            report['process_map_inserted'] += 1
        
        print(f"  ✓ Created {report['process_map_inserted']} process map entries")
        
        # ===================================================================
        # 2. CREATE PROCESS DEFINITION
        # ===================================================================
        print("\nCreating MDI Process Definition...")
        
        # Check if process_definitions table exists, create if not
        try:
            get_count(db, 'process_definitions')
        except:
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
        
        mdi_proc_def_id = db.insert("process_definitions", {
            "code": "PROC-MDI-001",
            "name": "MDI Manufacturing Process",
            "version": "1.0",
            "status": "Active",
            "effective_from": "2024-01-01",
            "effective_to": None
        })
        report['process_definitions_inserted'] += 1
        print(f"  ✓ Created Process Definition (ID: {mdi_proc_def_id})")
        
        # ===================================================================
        # 3. CREATE PROCESS STEPS
        # ===================================================================
        print("\nCreating MDI Process Steps...")
        
        # Check if process_steps table exists
        try:
            get_count(db, 'process_steps')
        except:
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
                    KEY ix_process_id (process_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            """)
        
        mdi_steps = [
            (1, "Procurement & Raw Material Management", "Procurement and quality control of raw materials", True, False),
            (2, "API Processing", "Processing and micronization of active pharmaceutical ingredient", True, False),
            (3, "MDI Formulation Preparation", "Preparation of drug suspension/solution and mixing with propellant", True, False),
            (4, "Device Components Manufacturing", "Manufacturing of canisters, valves, and actuators", True, False),
            (5, "MDI Filling & Assembly", "Filling canisters with formulation and propellant, valve crimping", True, False),
            (6, "In-Process Quality Control", "Quality testing during manufacturing process", True, False),
            (7, "Packaging", "Primary and secondary packaging with labeling", True, False),
            (8, "Quality Assurance Testing", "Final quality control and batch release testing", True, False),
            (9, "Sterilization", "Sterilization process if required", False, False),
            (10, "Warehousing & Distribution", "Storage and distribution of finished products", True, False),
            (11, "Waste Stream Management", "Waste identification, collection, treatment, and disposal", True, False)
        ]
        
        step_ids_map = {}
        for order, name, desc, mandatory, parallel in mdi_steps:
            step_id = db.insert("process_steps", {
                "process_id": mdi_proc_def_id,
                "step_order": order,
                "name": name,
                "description": desc,
                "mandatory": 1 if mandatory else 0,
                "can_be_parallel": 1 if parallel else 0
            })
            step_ids_map[order] = step_id
            report['process_steps_inserted'] += 1
        
        print(f"  ✓ Created {report['process_steps_inserted']} process steps")
        
        # ===================================================================
        # 4. INSERT/VERIFY 7Ps ELEMENTS FOR MDI
        # ===================================================================
        print("\nSetting up 7Ps Elements for MDI...")
        
        # Get or create departments
        dept_ids = {}
        departments = [
            ("Procurement", "PROC"),
            ("Manufacturing", "MFG"),
            ("Quality Assurance", "QA"),
            ("Warehouse", "WH"),
            ("Environmental Health & Safety", "EHS")
        ]
        
        for dept_name, code in departments:
            existing = db.select(f"SELECT department_id FROM departments WHERE DepartmentName = %s", [dept_name])
            if existing:
                dept_ids[dept_name] = existing[0]['department_id']
            else:
                dept_id = db.insert("departments", {"DepartmentName": dept_name})
                dept_ids[dept_name] = dept_id
        
        # Get or create people
        people_data = [
            ("Procurement Manager", "Procurement", "procurement@amneal.com"),
            ("API Processing Operator", "Manufacturing", "api@amneal.com"),
            ("Formulation Chemist", "Manufacturing", "formulation@amneal.com"),
            ("Filling Line Operator", "Manufacturing", "filling@amneal.com"),
            ("QA Analyst", "Quality Assurance", "qa@amneal.com"),
            ("Packaging Operator", "Manufacturing", "packaging@amneal.com"),
            ("Warehouse Manager", "Warehouse", "warehouse@amneal.com"),
            ("EHS Officer", "Environmental Health & Safety", "ehs@amneal.com")
        ]
        
        people_ids = {}
        for position, dept, email in people_data:
            dept_id = dept_ids.get(dept, 2)  # Default to Manufacturing
            existing = db.select("SELECT people_id FROM people WHERE Email = %s", [email])
            if existing:
                people_ids[position] = existing[0]['people_id']
            else:
                first_name = position.split()[0]
                last_name = " ".join(position.split()[1:]) if len(position.split()) > 1 else "User"
                person_id = db.insert("people", {
                    "FirstName": first_name,
                    "LastName": last_name,
                    "Email": email,
                    "Position": position,
                    "IsActive": 1,
                    "department_id": dept_id
                })
                people_ids[position] = person_id
        
        # Get or create areas
        areas_data = [
            ("Procurement Office", "Office", "PROC-OFF"),
            ("Raw Material Warehouse", "Storage", "RM-WH"),
            ("API Processing Area", "Production", "API-PROD"),
            ("Formulation Lab", "Laboratory", "FORM-LAB"),
            ("Filling Room", "Production", "FILL-ROOM"),
            ("Packaging Area", "Production", "PACK-AREA"),
            ("QA Laboratory", "Laboratory", "QA-LAB"),
            ("Finished Goods Warehouse", "Storage", "FG-WH"),
            ("Waste Storage Area", "Storage", "WASTE-STOR")
        ]
        
        area_ids = {}
        for area_name, area_type, location_code in areas_data:
            existing = db.select("SELECT area_id FROM areas WHERE area_name = %s", [area_name])
            if existing:
                area_ids[area_name] = existing[0]['area_id']
            else:
                area_id = db.insert("areas", {
                    "area_name": area_name,
                    "area_type": area_type,
                    "location_code": location_code,
                    "is_active": 1
                })
                area_ids[area_name] = area_id
        
        # Get or create equipment
        equipment_data = [
            ("API Micronizer", "Processing Equipment", "API-MIC-001", "API Processing Area"),
            ("Formulation Mixer", "Mixing Equipment", "FORM-MIX-001", "Formulation Lab"),
            ("MDI Filling Machine", "Filling Equipment", "MDI-FILL-001", "Filling Room"),
            ("Propellant Charging System", "Filling Equipment", "PROP-CHARGE-001", "Filling Room"),
            ("Valve Crimping Machine", "Assembly Equipment", "VALVE-CRIMP-001", "Filling Room"),
            ("Leak Tester", "Testing Equipment", "LEAK-TEST-001", "Filling Room"),
            ("APSD Testing Equipment", "Testing Equipment", "APSD-TEST-001", "QA Laboratory"),
            ("Packaging Line", "Packaging Equipment", "PACK-LINE-001", "Packaging Area"),
            ("Waste Compactor", "Waste Equipment", "WASTE-COMP-001", "Waste Storage Area")
        ]
        
        equipment_ids = {}
        for item_name, eq_type, serial, location in equipment_data:
            existing = db.select("SELECT equipment_id FROM equipment WHERE serial_number = %s", [serial])
            if existing:
                equipment_ids[item_name] = existing[0]['equipment_id']
            else:
                eq_id = db.insert("equipment", {
                    "item_name": item_name,
                    "equipment_type": eq_type,
                    "serial_number": serial,
                    "location": location,
                    "status": "Active"
                })
                equipment_ids[item_name] = eq_id
        
        # Get or create materials
        materials_data = [
            ("API Compound", "Active Ingredient", "kg"),
            ("HFA-134a Propellant", "Propellant", "kg"),
            ("HFA-227 Propellant", "Propellant", "kg"),
            ("Ethanol", "Solvent", "L"),
            ("Aluminum Canister", "Packaging Material", "units"),
            ("Valve Assembly", "Device Component", "units"),
            ("Actuator", "Device Component", "units"),
            ("Carton Box", "Packaging Material", "units"),
            ("Patient Information Leaflet", "Document", "units")
        ]
        
        material_ids = {}
        for mat_name, mat_type, unit in materials_data:
            existing = db.select("SELECT MaterialID FROM materials WHERE MaterialName = %s", [mat_name])
            if existing:
                material_ids[mat_name] = existing[0]['MaterialID']
            else:
                mat_id = db.insert("materials", {
                    "MaterialName": mat_name,
                    "MaterialType": mat_type,
                    "UnitOfMeasure": unit
                })
                material_ids[mat_name] = mat_id
        
        # Get or create energy
        energy_types = [
            ("Electrical", "Main electrical supply"),
            ("Compressed Air", "Compressed air for pneumatic equipment"),
            ("Steam", "Steam for sterilization"),
            ("Nitrogen", "Nitrogen for inert atmosphere")
        ]
        
        energy_ids = {}
        for energy_name, desc in energy_types:
            # Check energy type first
            et_existing = db.select("SELECT EnergyTypeID FROM energytype WHERE EnergyTypeName = %s", [energy_name])
            if not et_existing:
                et_id = db.insert("energytype", {"EnergyTypeName": energy_name, "Description": desc})
            else:
                et_id = et_existing[0]['EnergyTypeID']
            
            # Check energy
            e_existing = db.select("SELECT EnergyID FROM energy WHERE EnergyName = %s", [energy_name])
            if e_existing:
                energy_ids[energy_name] = e_existing[0]['EnergyID']
            else:
                e_id = db.insert("energy", {
                    "EnergyName": energy_name,
                    "EnergyTypeID": et_id,
                    "Description": desc
                })
                energy_ids[energy_name] = e_id
        
        print("  ✓ 7Ps elements ready")
        
        # ===================================================================
        # 5. LINK 7Ps TO PROCESS MAP NODES
        # ===================================================================
        print("\nLinking 7Ps Elements to Process Map...")
        
        # Link People to stages
        people_links = [
            (proc_stage_id, "Procurement Manager"),
            (api_stage_id, "API Processing Operator"),
            (form_stage_id, "Formulation Chemist"),
            (fill_stage_id, "Filling Line Operator"),
            (ipqc_stage_id, "QA Analyst"),
            (pack_stage_id, "Packaging Operator"),
            (qa_stage_id, "QA Analyst"),
            (wh_stage_id, "Warehouse Manager"),
            (waste_stage_id, "EHS Officer")
        ]
        
        for process_map_id, position in people_links:
            if position in people_ids:
                try:
                    db.insert("process_map_people", {
                        "process_map_id": process_map_id,
                        "people_id": people_ids[position]
                    })
                    report['7ps_links_created'] += 1
                except:
                    pass
        
        # Link Equipment
        equipment_links = [
            (api_stage_id, "API Micronizer"),
            (form_stage_id, "Formulation Mixer"),
            (fill_stage_id, "MDI Filling Machine"),
            (fill_stage_id, "Propellant Charging System"),
            (fill_stage_id, "Valve Crimping Machine"),
            (fill_stage_id, "Leak Tester"),
            (ipqc_stage_id, "APSD Testing Equipment"),
            (pack_stage_id, "Packaging Line"),
            (waste_stage_id, "Waste Compactor")
        ]
        
        for process_map_id, eq_name in equipment_links:
            if eq_name in equipment_ids:
                try:
                    db.insert("process_map_equipment", {
                        "process_map_id": process_map_id,
                        "equipment_id": equipment_ids[eq_name]
                    })
                    report['7ps_links_created'] += 1
                except:
                    pass
        
        # Link Areas
        area_links = [
            (proc_stage_id, "Procurement Office"),
            (proc_stage_id, "Raw Material Warehouse"),
            (api_stage_id, "API Processing Area"),
            (form_stage_id, "Formulation Lab"),
            (fill_stage_id, "Filling Room"),
            (pack_stage_id, "Packaging Area"),
            (ipqc_stage_id, "QA Laboratory"),
            (qa_stage_id, "QA Laboratory"),
            (wh_stage_id, "Finished Goods Warehouse"),
            (waste_stage_id, "Waste Storage Area")
        ]
        
        for process_map_id, area_name in area_links:
            if area_name in area_ids:
                try:
                    db.insert("process_map_area", {
                        "process_map_id": process_map_id,
                        "area_id": area_ids[area_name]
                    })
                    report['7ps_links_created'] += 1
                except:
                    pass
        
        # Link Materials
        material_links = [
            (proc_stage_id, "API Compound", 100.0),
            (proc_stage_id, "Aluminum Canister", 10000.0),
            (proc_stage_id, "Valve Assembly", 10000.0),
            (form_stage_id, "API Compound", 10.0),
            (form_stage_id, "HFA-134a Propellant", 50.0),
            (form_stage_id, "Ethanol", 20.0),
            (fill_stage_id, "HFA-134a Propellant", 100.0),
            (pack_stage_id, "Carton Box", 10000.0),
            (pack_stage_id, "Patient Information Leaflet", 10000.0)
        ]
        
        for process_map_id, mat_name, qty in material_links:
            if mat_name in material_ids:
                try:
                    db.insert("process_map_material", {
                        "process_map_id": process_map_id,
                        "material_id": material_ids[mat_name],
                        "quantity": qty
                    })
                    report['7ps_links_created'] += 1
                except:
                    pass
        
        # Link Energy
        energy_links = [
            (api_stage_id, "Electrical"),
            (form_stage_id, "Electrical"),
            (fill_stage_id, "Electrical"),
            (fill_stage_id, "Compressed Air"),
            (ipqc_stage_id, "Electrical"),
            (ster_stage_id, "Steam"),
            (waste_stage_id, "Electrical")
        ]
        
        for process_map_id, energy_name in energy_links:
            if energy_name in energy_ids:
                try:
                    db.insert("process_map_energy", {
                        "process_map_id": process_map_id,
                        "energy_id": energy_ids[energy_name]
                    })
                    report['7ps_links_created'] += 1
                except:
                    pass
        
        print(f"  ✓ Created {report['7ps_links_created']} 7Ps links")
        
        # ===================================================================
        # 6. CREATE TASKS FOR MDI PROCESS
        # ===================================================================
        print("\nCreating Tasks for MDI Process...")
        
        tasks_data = [
            ("Procure API Compound", "Purchase API from qualified supplier", "Operational Task", "High", proc_stage_id, people_ids.get("Procurement Manager", 1), dept_ids.get("Procurement", 1)),
            ("Receive & Inspect Raw Materials", "Receive and perform incoming QC on raw materials", "Compliance Task", "High", proc_stage_id, people_ids.get("Procurement Manager", 1), dept_ids.get("Procurement", 1)),
            ("Micronize API", "Micronize API to required particle size", "Operational Task", "High", api_stage_id, people_ids.get("API Processing Operator", 1), dept_ids.get("Manufacturing", 2)),
            ("Prepare Drug Suspension", "Prepare drug suspension/solution for MDI", "Operational Task", "High", form_stage_id, people_ids.get("Formulation Chemist", 1), dept_ids.get("Manufacturing", 2)),
            ("Mix with Propellant", "Mix drug suspension with HFA propellant", "Operational Task", "High", form_stage_id, people_ids.get("Formulation Chemist", 1), dept_ids.get("Manufacturing", 2)),
            ("Fill Canisters", "Fill canisters with formulation", "Operational Task", "High", fill_stage_id, people_ids.get("Filling Line Operator", 1), dept_ids.get("Manufacturing", 2)),
            ("Charge Propellant", "Charge propellant into filled canisters", "Operational Task", "High", fill_stage_id, people_ids.get("Filling Line Operator", 1), dept_ids.get("Manufacturing", 2)),
            ("Crimp Valves", "Crimp valves onto filled canisters", "Operational Task", "High", fill_stage_id, people_ids.get("Filling Line Operator", 1), dept_ids.get("Manufacturing", 2)),
            ("Perform Leak Testing", "Test canisters for leaks", "Compliance Task", "High", ipqc_stage_id, people_ids.get("QA Analyst", 1), dept_ids.get("Quality Assurance", 1)),
            ("Test Dose Uniformity", "Perform delivered dose uniformity testing", "Compliance Task", "High", ipqc_stage_id, people_ids.get("QA Analyst", 1), dept_ids.get("Quality Assurance", 1)),
            ("Package MDI Products", "Package finished MDI products", "Operational Task", "High", pack_stage_id, people_ids.get("Packaging Operator", 1), dept_ids.get("Manufacturing", 2)),
            ("Perform Batch Release Testing", "Complete final QA testing for batch release", "Compliance Task", "High", qa_stage_id, people_ids.get("QA Analyst", 1), dept_ids.get("Quality Assurance", 1)),
            ("Store Finished Products", "Store finished products in warehouse", "Operational Task", "Medium", wh_stage_id, people_ids.get("Warehouse Manager", 1), dept_ids.get("Warehouse", 1)),
            ("Collect Manufacturing Waste", "Collect and segregate waste from manufacturing", "Compliance Task", "High", waste_stage_id, people_ids.get("EHS Officer", 1), dept_ids.get("Environmental Health & Safety", 1)),
            ("Dispose of Hazardous Waste", "Arrange disposal of hazardous waste", "Compliance Task", "High", waste_stage_id, people_ids.get("EHS Officer", 1), dept_ids.get("Environmental Health & Safety", 1))
        ]
        
        for task_name, task_desc, task_type, priority, step_id, assigned_to, dept_id in tasks_data:
            try:
                due_date = (datetime.now() + timedelta(days=7)).strftime('%Y-%m-%d')
                task_id = db.insert("tasks", {
                    "task_name": task_name,
                    "task_description": task_desc,
                    "task_type": task_type,
                    "priority": priority,
                    "status": "Not Started",
                    "due_date": due_date,
                    "assigned_to": assigned_to,
                    "department_id": dept_id
                })
                report['tasks_inserted'] += 1
            except Exception as e:
                report['errors'].append(f"Task '{task_name}': {str(e)}")
        
        print(f"  ✓ Created {report['tasks_inserted']} tasks")
        
        # ===================================================================
        # 7. CREATE WASTE STREAM RECORDS
        # ===================================================================
        print("\nCreating Waste Stream Records for MDI...")
        
        # Get or create waste categories
        waste_categories = [
            ("Hazardous Chemical Waste", "Waste from API processing and formulation"),
            ("Propellant Waste", "Waste HFA propellant"),
            ("Packaging Waste", "Non-hazardous packaging materials"),
            ("Contaminated Materials", "Materials contaminated with API"),
            ("Solvent Waste", "Waste solvents from cleaning")
        ]
        
        # Check wastecategory table structure
        try:
            # Check if wastetype table exists, create if not
            try:
                get_count(db, 'wastetype')
            except:
                db.execute("""
                    CREATE TABLE IF NOT EXISTS wastetype (
                        WasteTypeID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                        WasteTypeName varchar(50) NOT NULL,
                        Description text DEFAULT NULL,
                        created_at timestamp NOT NULL DEFAULT current_timestamp(),
                        updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                        PRIMARY KEY (WasteTypeID),
                        UNIQUE KEY WasteTypeName (WasteTypeName)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
                """)
            
            # Get or create waste type
            waste_type_existing = db.select("SELECT WasteTypeID FROM wastetype WHERE WasteTypeName = %s", ["Hazardous"])
            if not waste_type_existing:
                waste_type_id = db.insert("wastetype", {"WasteTypeName": "Hazardous"})
            else:
                waste_type_id = waste_type_existing[0]['WasteTypeID']
            
            # Get or create non-hazardous waste type
            nh_waste_type_existing = db.select("SELECT WasteTypeID FROM wastetype WHERE WasteTypeName = %s", ["Non-Hazardous"])
            if not nh_waste_type_existing:
                nh_waste_type_id = db.insert("wastetype", {"WasteTypeName": "Non-Hazardous"})
            else:
                nh_waste_type_id = nh_waste_type_existing[0]['WasteTypeID']
            
            waste_cat_ids = {}
            for cat_name, desc in waste_categories:
                existing = db.select("SELECT WasteCategoryID FROM wastecategory WHERE WasteCategoryName = %s", [cat_name])
                if existing:
                    waste_cat_ids[cat_name] = existing[0]['WasteCategoryID']
                else:
                    # Determine waste type based on category
                    wt_id = nh_waste_type_id if "Packaging" in cat_name else waste_type_id
                    cat_id = db.insert("wastecategory", {
                        "WasteTypeID": wt_id,
                        "WasteCategoryName": cat_name,
                        "Description": desc
                    })
                    waste_cat_ids[cat_name] = cat_id
            
            # Ensure measurement units exist
            units = ["kg", "L", "g"]
            unit_ids = {}
            for unit in units:
                existing = db.select("SELECT UnitID FROM measurementunit WHERE UnitName = %s", [unit])
                if existing:
                    unit_ids[unit] = existing[0]['UnitID']
                else:
                    unit_id = db.insert("measurementunit", {"UnitName": unit})
                    unit_ids[unit] = unit_id
            
            # Create waste records linked to process stages
            waste_records = [
                ("API Processing Waste", waste_cat_ids.get("Hazardous Chemical Waste", 1), 5.5, "kg", api_stage_id),
                ("Propellant Waste", waste_cat_ids.get("Propellant Waste", 1), 2.3, "kg", fill_stage_id),
                ("Packaging Waste", waste_cat_ids.get("Packaging Waste", 1), 15.0, "kg", pack_stage_id),
                ("Contaminated Cleaning Materials", waste_cat_ids.get("Contaminated Materials", 1), 3.2, "kg", form_stage_id),
                ("Solvent Waste", waste_cat_ids.get("Solvent Waste", 1), 8.5, "L", form_stage_id)
            ]
            
            for waste_name, cat_id, amount, unit, process_map_id in waste_records:
                try:
                    unit_id = unit_ids.get(unit)
                    
                    record_id = db.insert("wastemanagementrecord", {
                        "WasteCategoryID": cat_id,
                        "Amount": amount,
                        "UnitID": unit_id,
                        "DisposalDate": (datetime.now() + timedelta(days=30)).strftime('%Y-%m-%d'),
                        "Comments": f"Waste from MDI manufacturing process - {waste_name}"
                    })
                    report['waste_records_inserted'] += 1
                except Exception as e:
                    report['errors'].append(f"Waste record '{waste_name}': {str(e)}")
            
            print(f"  ✓ Created {report['waste_records_inserted']} waste records")
        except Exception as e:
            print(f"  ⚠ Waste records creation skipped: {str(e)}")
            report['errors'].append(f"Waste records: {str(e)}")
        
        # ===================================================================
        # FINALIZE
        # ===================================================================
        db.execute("SET FOREIGN_KEY_CHECKS = 1")
        
        print("\n" + "="*80)
        print("MDI MANUFACTURING FLOW - EXECUTION SUMMARY")
        print("="*80)
        print(f"Process Map Entries:     {report['process_map_inserted']}")
        print(f"Process Definitions:      {report['process_definitions_inserted']}")
        print(f"Process Steps:            {report['process_steps_inserted']}")
        print(f"Tasks Created:           {report['tasks_inserted']}")
        print(f"Waste Records:           {report['waste_records_inserted']}")
        print(f"7Ps Links Created:       {report['7ps_links_created']}")
        if report['errors']:
            print(f"\nErrors: {len(report['errors'])}")
            for error in report['errors'][:5]:  # Show first 5 errors
                print(f"  ⚠ {error}")
        print("="*80)
        print("✓ MDI Manufacturing Flow Created Successfully!")
        print("="*80)
        
        return report
        
    except Exception as e:
        db.execute("SET FOREIGN_KEY_CHECKS = 1")
        print(f"\n✗ ERROR: {str(e)}")
        import traceback
        traceback.print_exc()
        report['errors'].append(str(e))
        return report


def insert_dpi_manufacturing_flow():
    """
    Insert complete DPI (Dry Powder Inhaler) manufacturing process flow
    From procurement through to finished product shipment, including waste streams
    """
    db = SQL()
    
    print("\n\n" + "="*80)
    print("DPI (Dry Powder Inhaler) Manufacturing Process Flow")
    print("="*80)
    print(f"Start Time: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
    
    report = {
        'process_map_inserted': 0,
        'process_definitions_inserted': 0,
        'process_steps_inserted': 0,
        'tasks_inserted': 0,
        'waste_records_inserted': 0,
        '7ps_links_created': 0,
        'errors': []
    }
    
    try:
        db.execute("SET FOREIGN_KEY_CHECKS = 0")
        
        # ===================================================================
        # 1. CREATE PROCESS MAP HIERARCHY FOR DPI MANUFACTURING
        # ===================================================================
        print("Creating DPI Process Map Hierarchy...")
        
        # Main Process: DPI Manufacturing
        dpi_process_id = db.insert("process_map", {
            "type": "process",
            "text": "DPI (Dry Powder Inhaler) Manufacturing",
            "parent": None
        })
        report['process_map_inserted'] += 1
        print(f"  ✓ Created DPI Manufacturing Process (ID: {dpi_process_id})")
        
        # Stage 1: Procurement (same as MDI)
        proc_stage_id = db.insert("process_map", {
            "type": "step",
            "text": "1. Procurement & Raw Material Management",
            "parent": dpi_process_id
        })
        report['process_map_inserted'] += 1
        
        proc_substeps = [
            ("1.1", "Supplier Qualification & Selection", proc_stage_id),
            ("1.2", "Purchase Order Processing", proc_stage_id),
            ("1.3", "Incoming Material Receipt", proc_stage_id),
            ("1.4", "Incoming Material Quality Control", proc_stage_id),
            ("1.5", "Raw Material Storage & Inventory Management", proc_stage_id)
        ]
        
        proc_substep_ids = {}
        for code, name, parent in proc_substeps:
            substep_id = db.insert("process_map", {
                "type": "substep",
                "text": f"{code} {name}",
                "parent": parent
            })
            proc_substep_ids[code] = substep_id
            report['process_map_inserted'] += 1
        
        # Stage 2: API Processing (same as MDI)
        api_stage_id = db.insert("process_map", {
            "type": "step",
            "text": "2. API (Active Pharmaceutical Ingredient) Processing",
            "parent": dpi_process_id
        })
        report['process_map_inserted'] += 1
        
        api_substeps = [
            ("2.1", "API Receipt & Verification", api_stage_id),
            ("2.2", "API Micronization", api_stage_id),
            ("2.3", "Particle Size Distribution Analysis", api_stage_id),
            ("2.4", "Crystallinity and Surface Morphology Testing", api_stage_id),
            ("2.5", "API Storage (Controlled Conditions)", api_stage_id)
        ]
        
        api_substep_ids = {}
        for code, name, parent in api_substeps:
            substep_id = db.insert("process_map", {
                "type": "substep",
                "text": f"{code} {name}",
                "parent": parent
            })
            api_substep_ids[code] = substep_id
            report['process_map_inserted'] += 1
        
        # Stage 3: Formulation Preparation (DPI Specific)
        form_stage_id = db.insert("process_map", {
            "type": "step",
            "text": "3. DPI Formulation Preparation",
            "parent": dpi_process_id
        })
        report['process_map_inserted'] += 1
        
        form_substeps = [
            ("3.1", "Carrier Selection (e.g., Lactose)", form_stage_id),
            ("3.2", "Blending of Micronized API with Carrier", form_stage_id),
            ("3.3", "Homogenization", form_stage_id),
            ("3.4", "Particle Size Distribution Analysis", form_stage_id),
            ("3.5", "Moisture Content Analysis", form_stage_id),
            ("3.6", "In-process Quality Testing", form_stage_id)
        ]
        
        form_substep_ids = {}
        for code, name, parent in form_substeps:
            substep_id = db.insert("process_map", {
                "type": "substep",
                "text": f"{code} {name}",
                "parent": parent
            })
            form_substep_ids[code] = substep_id
            report['process_map_inserted'] += 1
        
        # Stage 4: Device Components Manufacturing
        device_stage_id = db.insert("process_map", {
            "type": "step",
            "text": "4. Device Components Manufacturing",
            "parent": dpi_process_id
        })
        report['process_map_inserted'] += 1
        
        device_substeps = [
            ("4.1", "Injection Molding of Plastic Components", device_stage_id),
            ("4.2", "Reservoir/Blister Manufacturing", device_stage_id),
            ("4.3", "Device Assembly", device_stage_id),
            ("4.4", "Component Quality Inspection", device_stage_id)
        ]
        
        device_substep_ids = {}
        for code, name, parent in device_substeps:
            substep_id = db.insert("process_map", {
                "type": "substep",
                "text": f"{code} {name}",
                "parent": parent
            })
            device_substep_ids[code] = substep_id
            report['process_map_inserted'] += 1
        
        # Stage 5: Filling & Assembly (DPI Specific)
        fill_stage_id = db.insert("process_map", {
            "type": "step",
            "text": "5. DPI Filling & Assembly",
            "parent": dpi_process_id
        })
        report['process_map_inserted'] += 1
        
        fill_substeps = [
            ("5.1", "Powder Filling into Blisters/Reservoirs", fill_stage_id),
            ("5.2", "Device Assembly", fill_stage_id),
            ("5.3", "Weight Checks", fill_stage_id),
            ("5.4", "Dose Uniformity Testing", fill_stage_id)
        ]
        
        fill_substep_ids = {}
        for code, name, parent in fill_substeps:
            substep_id = db.insert("process_map", {
                "type": "substep",
                "text": f"{code} {name}",
                "parent": parent
            })
            fill_substep_ids[code] = substep_id
            report['process_map_inserted'] += 1
        
        # Stage 6: In-Process Quality Control
        ipqc_stage_id = db.insert("process_map", {
            "type": "step",
            "text": "6. In-Process Quality Control",
            "parent": dpi_process_id
        })
        report['process_map_inserted'] += 1
        
        ipqc_substeps = [
            ("6.1", "Weight Checks", ipqc_stage_id),
            ("6.2", "Dose Uniformity Testing", ipqc_stage_id),
            ("6.3", "Particle Size Distribution Analysis", ipqc_stage_id),
            ("6.4", "Moisture Content Analysis", ipqc_stage_id)
        ]
        
        ipqc_substep_ids = {}
        for code, name, parent in ipqc_substeps:
            substep_id = db.insert("process_map", {
                "type": "substep",
                "text": f"{code} {name}",
                "parent": parent
            })
            ipqc_substep_ids[code] = substep_id
            report['process_map_inserted'] += 1
        
        # Stage 7: Packaging
        pack_stage_id = db.insert("process_map", {
            "type": "step",
            "text": "7. Packaging",
            "parent": dpi_process_id
        })
        report['process_map_inserted'] += 1
        
        pack_substeps = [
            ("7.1", "Primary Packaging (Blister Packing)", pack_stage_id),
            ("7.2", "Secondary Packaging (Carton Boxing)", pack_stage_id),
            ("7.3", "Patient Information Leaflet Insertion", pack_stage_id),
            ("7.4", "Batch Number & Expiry Date Labeling", pack_stage_id)
        ]
        
        pack_substep_ids = {}
        for code, name, parent in pack_substeps:
            substep_id = db.insert("process_map", {
                "type": "substep",
                "text": f"{code} {name}",
                "parent": parent
            })
            pack_substep_ids[code] = substep_id
            report['process_map_inserted'] += 1
        
        # Stage 8: Quality Assurance Testing
        qa_stage_id = db.insert("process_map", {
            "type": "step",
            "text": "8. Quality Assurance Testing",
            "parent": dpi_process_id
        })
        report['process_map_inserted'] += 1
        
        qa_substeps = [
            ("8.1", "Visual Inspection", qa_stage_id),
            ("8.2", "Aerodynamic Particle Size Distribution (APSD)", qa_stage_id),
            ("8.3", "Delivered Dose Uniformity", qa_stage_id),
            ("8.4", "Moisture Content Analysis", qa_stage_id),
            ("8.5", "Batch Release Testing", qa_stage_id)
        ]
        
        qa_substep_ids = {}
        for code, name, parent in qa_substeps:
            substep_id = db.insert("process_map", {
                "type": "substep",
                "text": f"{code} {name}",
                "parent": parent
            })
            qa_substep_ids[code] = substep_id
            report['process_map_inserted'] += 1
        
        # Stage 9: Sterilization (if required)
        ster_stage_id = db.insert("process_map", {
            "type": "step",
            "text": "9. Sterilization (if required)",
            "parent": dpi_process_id
        })
        report['process_map_inserted'] += 1
        
        ster_substeps = [
            ("9.1", "Ethylene Oxide (EO) Sterilization", ster_stage_id),
            ("9.2", "Bioburden Testing", ster_stage_id),
            ("9.3", "Residual EO Testing", ster_stage_id),
            ("9.4", "Sterility Testing", ster_stage_id)
        ]
        
        ster_substep_ids = {}
        for code, name, parent in ster_substeps:
            substep_id = db.insert("process_map", {
                "type": "substep",
                "text": f"{code} {name}",
                "parent": parent
            })
            ster_substep_ids[code] = substep_id
            report['process_map_inserted'] += 1
        
        # Stage 10: Warehousing & Distribution
        wh_stage_id = db.insert("process_map", {
            "type": "step",
            "text": "10. Warehousing & Distribution",
            "parent": dpi_process_id
        })
        report['process_map_inserted'] += 1
        
        wh_substeps = [
            ("10.1", "Storage under Controlled Conditions", wh_stage_id),
            ("10.2", "Shipment Preparation", wh_stage_id),
            ("10.3", "Distribution to Wholesalers/Pharmacies", wh_stage_id),
            ("10.4", "Cold Chain Management (if required)", wh_stage_id)
        ]
        
        wh_substep_ids = {}
        for code, name, parent in wh_substeps:
            substep_id = db.insert("process_map", {
                "type": "substep",
                "text": f"{code} {name}",
                "parent": parent
            })
            wh_substep_ids[code] = substep_id
            report['process_map_inserted'] += 1
        
        # Stage 11: Waste Stream Management
        waste_stage_id = db.insert("process_map", {
            "type": "step",
            "text": "11. Waste Stream Management",
            "parent": dpi_process_id
        })
        report['process_map_inserted'] += 1
        
        waste_substeps = [
            ("11.1", "Waste Identification & Classification", waste_stage_id),
            ("11.2", "Waste Segregation", waste_stage_id),
            ("11.3", "Waste Collection & Storage", waste_stage_id),
            ("11.4", "Waste Treatment (if applicable)", waste_stage_id),
            ("11.5", "Waste Disposal", waste_stage_id),
            ("11.6", "Waste Documentation & Reporting", waste_stage_id)
        ]
        
        waste_substep_ids = {}
        for code, name, parent in waste_substeps:
            substep_id = db.insert("process_map", {
                "type": "substep",
                "text": f"{code} {name}",
                "parent": parent
            })
            waste_substep_ids[code] = substep_id
            report['process_map_inserted'] += 1
        
        print(f"  ✓ Created {report['process_map_inserted']} process map entries")
        
        # ===================================================================
        # 2. CREATE PROCESS DEFINITION
        # ===================================================================
        print("\nCreating DPI Process Definition...")
        
        dpi_proc_def_id = db.insert("process_definitions", {
            "code": "PROC-DPI-001",
            "name": "DPI Manufacturing Process",
            "version": "1.0",
            "status": "Active",
            "effective_from": "2024-01-01",
            "effective_to": None
        })
        report['process_definitions_inserted'] += 1
        print(f"  ✓ Created Process Definition (ID: {dpi_proc_def_id})")
        
        # ===================================================================
        # 3. CREATE PROCESS STEPS
        # ===================================================================
        print("\nCreating DPI Process Steps...")
        
        dpi_steps = [
            (1, "Procurement & Raw Material Management", "Procurement and quality control of raw materials", True, False),
            (2, "API Processing", "Processing and micronization of active pharmaceutical ingredient", True, False),
            (3, "DPI Formulation Preparation", "Blending of micronized API with carrier (e.g., lactose)", True, False),
            (4, "Device Components Manufacturing", "Manufacturing of device components and reservoirs", True, False),
            (5, "DPI Filling & Assembly", "Powder filling into blisters/reservoirs and device assembly", True, False),
            (6, "In-Process Quality Control", "Quality testing during manufacturing process", True, False),
            (7, "Packaging", "Primary and secondary packaging with labeling", True, False),
            (8, "Quality Assurance Testing", "Final quality control and batch release testing", True, False),
            (9, "Sterilization", "Sterilization process if required", False, False),
            (10, "Warehousing & Distribution", "Storage and distribution of finished products", True, False),
            (11, "Waste Stream Management", "Waste identification, collection, treatment, and disposal", True, False)
        ]
        
        step_ids_map = {}
        for order, name, desc, mandatory, parallel in dpi_steps:
            step_id = db.insert("process_steps", {
                "process_id": dpi_proc_def_id,
                "step_order": order,
                "name": name,
                "description": desc,
                "mandatory": 1 if mandatory else 0,
                "can_be_parallel": 1 if parallel else 0
            })
            step_ids_map[order] = step_id
            report['process_steps_inserted'] += 1
        
        print(f"  ✓ Created {report['process_steps_inserted']} process steps")
        
        # ===================================================================
        # 4. INSERT/VERIFY 7Ps ELEMENTS FOR DPI
        # ===================================================================
        print("\nSetting up 7Ps Elements for DPI...")
        
        # Get existing departments and people (reuse from MDI setup)
        dept_ids = {}
        for dept_name in ["Procurement", "Manufacturing", "Quality Assurance", "Warehouse", "Environmental Health & Safety"]:
            existing = db.select("SELECT department_id FROM departments WHERE DepartmentName = %s", [dept_name])
            if existing:
                dept_ids[dept_name] = existing[0]['department_id']
        
        people_ids = {}
        people_positions = [
            "Procurement Manager", "API Processing Operator", "Formulation Chemist",
            "Filling Line Operator", "QA Analyst", "Packaging Operator",
            "Warehouse Manager", "EHS Officer"
        ]
        
        for position in people_positions:
            existing = db.select("SELECT people_id FROM people WHERE Position = %s LIMIT 1", [position])
            if existing:
                people_ids[position] = existing[0]['people_id']
        
        # Get or create DPI-specific areas
        dpi_areas_data = [
            ("DPI Formulation Lab", "Laboratory", "DPI-FORM-LAB"),
            ("DPI Filling Room", "Production", "DPI-FILL-ROOM"),
            ("DPI Packaging Area", "Production", "DPI-PACK-AREA")
        ]
        
        area_ids = {}
        for area_name, area_type, location_code in dpi_areas_data:
            existing = db.select("SELECT area_id FROM areas WHERE area_name = %s", [area_name])
            if existing:
                area_ids[area_name] = existing[0]['area_id']
            else:
                area_id = db.insert("areas", {
                    "area_name": area_name,
                    "area_type": area_type,
                    "location_code": location_code,
                    "is_active": 1
                })
                area_ids[area_name] = area_id
        
        # Get existing areas
        existing_areas = db.select("SELECT area_id, area_name FROM areas")
        for area in existing_areas:
            area_ids[area['area_name']] = area['area_id']
        
        # Get or create DPI-specific equipment
        dpi_equipment_data = [
            ("DPI Blending Mixer", "Mixing Equipment", "DPI-BLEND-001", "DPI Formulation Lab"),
            ("DPI Powder Filling Machine", "Filling Equipment", "DPI-FILL-001", "DPI Filling Room"),
            ("DPI Device Assembly Line", "Assembly Equipment", "DPI-ASSEM-001", "DPI Filling Room"),
            ("DPI Moisture Analyzer", "Testing Equipment", "DPI-MOIST-001", "QA Laboratory")
        ]
        
        equipment_ids = {}
        for item_name, eq_type, serial, location in dpi_equipment_data:
            existing = db.select("SELECT equipment_id FROM equipment WHERE serial_number = %s", [serial])
            if existing:
                equipment_ids[item_name] = existing[0]['equipment_id']
            else:
                eq_id = db.insert("equipment", {
                    "item_name": item_name,
                    "equipment_type": eq_type,
                    "serial_number": serial,
                    "location": location,
                    "status": "Active"
                })
                equipment_ids[item_name] = eq_id
        
        # Get existing equipment
        existing_eq = db.select("SELECT equipment_id, item_name FROM equipment")
        for eq in existing_eq:
            equipment_ids[eq['item_name']] = eq['equipment_id']
        
        # Get or create DPI-specific materials
        dpi_materials_data = [
            ("Lactose Monohydrate", "Carrier", "kg"),
            ("DPI Blister Pack", "Packaging Material", "units"),
            ("DPI Device Reservoir", "Device Component", "units")
        ]
        
        material_ids = {}
        for mat_name, mat_type, unit in dpi_materials_data:
            existing = db.select("SELECT MaterialID FROM materials WHERE MaterialName = %s", [mat_name])
            if existing:
                material_ids[mat_name] = existing[0]['MaterialID']
            else:
                mat_id = db.insert("materials", {
                    "MaterialName": mat_name,
                    "MaterialType": mat_type,
                    "UnitOfMeasure": unit
                })
                material_ids[mat_name] = mat_id
        
        # Get existing materials
        existing_mats = db.select("SELECT MaterialID, MaterialName FROM materials")
        for mat in existing_mats:
            material_ids[mat['MaterialName']] = mat['MaterialID']
        
        # Get existing energy
        energy_ids = {}
        existing_energy = db.select("SELECT EnergyID, EnergyName FROM energy")
        for energy in existing_energy:
            energy_ids[energy['EnergyName']] = energy['EnergyID']
        
        print("  ✓ 7Ps elements ready")
        
        # ===================================================================
        # 5. LINK 7Ps TO PROCESS MAP NODES
        # ===================================================================
        print("\nLinking 7Ps Elements to Process Map...")
        
        # Link People
        people_links = [
            (proc_stage_id, "Procurement Manager"),
            (api_stage_id, "API Processing Operator"),
            (form_stage_id, "Formulation Chemist"),
            (fill_stage_id, "Filling Line Operator"),
            (ipqc_stage_id, "QA Analyst"),
            (pack_stage_id, "Packaging Operator"),
            (qa_stage_id, "QA Analyst"),
            (wh_stage_id, "Warehouse Manager"),
            (waste_stage_id, "EHS Officer")
        ]
        
        for process_map_id, position in people_links:
            if position in people_ids:
                try:
                    db.insert("process_map_people", {
                        "process_map_id": process_map_id,
                        "people_id": people_ids[position]
                    })
                    report['7ps_links_created'] += 1
                except:
                    pass
        
        # Link Equipment
        equipment_links = [
            (api_stage_id, "API Micronizer"),
            (form_stage_id, "DPI Blending Mixer"),
            (fill_stage_id, "DPI Powder Filling Machine"),
            (fill_stage_id, "DPI Device Assembly Line"),
            (ipqc_stage_id, "DPI Moisture Analyzer"),
            (pack_stage_id, "Packaging Line"),
            (waste_stage_id, "Waste Compactor")
        ]
        
        for process_map_id, eq_name in equipment_links:
            if eq_name in equipment_ids:
                try:
                    db.insert("process_map_equipment", {
                        "process_map_id": process_map_id,
                        "equipment_id": equipment_ids[eq_name]
                    })
                    report['7ps_links_created'] += 1
                except:
                    pass
        
        # Link Areas
        area_links = [
            (proc_stage_id, "Procurement Office"),
            (proc_stage_id, "Raw Material Warehouse"),
            (api_stage_id, "API Processing Area"),
            (form_stage_id, "DPI Formulation Lab"),
            (fill_stage_id, "DPI Filling Room"),
            (pack_stage_id, "DPI Packaging Area"),
            (ipqc_stage_id, "QA Laboratory"),
            (qa_stage_id, "QA Laboratory"),
            (wh_stage_id, "Finished Goods Warehouse"),
            (waste_stage_id, "Waste Storage Area")
        ]
        
        for process_map_id, area_name in area_links:
            if area_name in area_ids:
                try:
                    db.insert("process_map_area", {
                        "process_map_id": process_map_id,
                        "area_id": area_ids[area_name]
                    })
                    report['7ps_links_created'] += 1
                except:
                    pass
        
        # Link Materials
        material_links = [
            (proc_stage_id, "API Compound", 100.0),
            (proc_stage_id, "Lactose Monohydrate", 200.0),
            (proc_stage_id, "DPI Blister Pack", 10000.0),
            (form_stage_id, "API Compound", 10.0),
            (form_stage_id, "Lactose Monohydrate", 50.0),
            (fill_stage_id, "DPI Blister Pack", 10000.0),
            (fill_stage_id, "DPI Device Reservoir", 10000.0),
            (pack_stage_id, "Carton Box", 10000.0),
            (pack_stage_id, "Patient Information Leaflet", 10000.0)
        ]
        
        for process_map_id, mat_name, qty in material_links:
            if mat_name in material_ids:
                try:
                    db.insert("process_map_material", {
                        "process_map_id": process_map_id,
                        "material_id": material_ids[mat_name],
                        "quantity": qty
                    })
                    report['7ps_links_created'] += 1
                except:
                    pass
        
        # Link Energy
        energy_links = [
            (api_stage_id, "Electrical"),
            (form_stage_id, "Electrical"),
            (fill_stage_id, "Electrical"),
            (fill_stage_id, "Compressed Air"),
            (ipqc_stage_id, "Electrical"),
            (ster_stage_id, "Steam"),
            (waste_stage_id, "Electrical")
        ]
        
        for process_map_id, energy_name in energy_links:
            if energy_name in energy_ids:
                try:
                    db.insert("process_map_energy", {
                        "process_map_id": process_map_id,
                        "energy_id": energy_ids[energy_name]
                    })
                    report['7ps_links_created'] += 1
                except:
                    pass
        
        print(f"  ✓ Created {report['7ps_links_created']} 7Ps links")
        
        # ===================================================================
        # 6. CREATE TASKS FOR DPI PROCESS
        # ===================================================================
        print("\nCreating Tasks for DPI Process...")
        
        tasks_data = [
            ("Procure API Compound", "Purchase API from qualified supplier", "Operational Task", "High", proc_stage_id, people_ids.get("Procurement Manager", 1), dept_ids.get("Procurement", 1)),
            ("Procure Lactose Carrier", "Purchase lactose monohydrate carrier", "Operational Task", "High", proc_stage_id, people_ids.get("Procurement Manager", 1), dept_ids.get("Procurement", 1)),
            ("Receive & Inspect Raw Materials", "Receive and perform incoming QC", "Compliance Task", "High", proc_stage_id, people_ids.get("Procurement Manager", 1), dept_ids.get("Procurement", 1)),
            ("Micronize API", "Micronize API to required particle size", "Operational Task", "High", api_stage_id, people_ids.get("API Processing Operator", 1), dept_ids.get("Manufacturing", 2)),
            ("Blend API with Lactose", "Blend micronized API with carrier", "Operational Task", "High", form_stage_id, people_ids.get("Formulation Chemist", 1), dept_ids.get("Manufacturing", 2)),
            ("Homogenize Powder Blend", "Homogenize powder blend", "Operational Task", "High", form_stage_id, people_ids.get("Formulation Chemist", 1), dept_ids.get("Manufacturing", 2)),
            ("Fill Powder into Blisters", "Fill powder formulation into blisters", "Operational Task", "High", fill_stage_id, people_ids.get("Filling Line Operator", 1), dept_ids.get("Manufacturing", 2)),
            ("Assemble DPI Devices", "Assemble device components", "Operational Task", "High", fill_stage_id, people_ids.get("Filling Line Operator", 1), dept_ids.get("Manufacturing", 2)),
            ("Test Moisture Content", "Perform moisture content analysis", "Compliance Task", "High", ipqc_stage_id, people_ids.get("QA Analyst", 1), dept_ids.get("Quality Assurance", 1)),
            ("Test Dose Uniformity", "Perform delivered dose uniformity testing", "Compliance Task", "High", ipqc_stage_id, people_ids.get("QA Analyst", 1), dept_ids.get("Quality Assurance", 1)),
            ("Package DPI Products", "Package finished DPI products", "Operational Task", "High", pack_stage_id, people_ids.get("Packaging Operator", 1), dept_ids.get("Manufacturing", 2)),
            ("Perform Batch Release Testing", "Complete final QA testing", "Compliance Task", "High", qa_stage_id, people_ids.get("QA Analyst", 1), dept_ids.get("Quality Assurance", 1)),
            ("Store Finished Products", "Store finished products in warehouse", "Operational Task", "Medium", wh_stage_id, people_ids.get("Warehouse Manager", 1), dept_ids.get("Warehouse", 1)),
            ("Collect Manufacturing Waste", "Collect and segregate waste", "Compliance Task", "High", waste_stage_id, people_ids.get("EHS Officer", 1), dept_ids.get("Environmental Health & Safety", 1)),
            ("Dispose of Hazardous Waste", "Arrange disposal of hazardous waste", "Compliance Task", "High", waste_stage_id, people_ids.get("EHS Officer", 1), dept_ids.get("Environmental Health & Safety", 1))
        ]
        
        for task_name, task_desc, task_type, priority, step_id, assigned_to, dept_id in tasks_data:
            try:
                due_date = (datetime.now() + timedelta(days=7)).strftime('%Y-%m-%d')
                task_id = db.insert("tasks", {
                    "task_name": task_name,
                    "task_description": task_desc,
                    "task_type": task_type,
                    "priority": priority,
                    "status": "Not Started",
                    "due_date": due_date,
                    "assigned_to": assigned_to,
                    "department_id": dept_id
                })
                report['tasks_inserted'] += 1
            except Exception as e:
                report['errors'].append(f"Task '{task_name}': {str(e)}")
        
        print(f"  ✓ Created {report['tasks_inserted']} tasks")
        
        # ===================================================================
        # 7. CREATE WASTE STREAM RECORDS
        # ===================================================================
        print("\nCreating Waste Stream Records for DPI...")
        
        try:
            # Get waste types
            waste_type_existing = db.select("SELECT WasteTypeID FROM wastetype WHERE WasteTypeName = %s", ["Hazardous"])
            waste_type_id = waste_type_existing[0]['WasteTypeID'] if waste_type_existing else 1
            
            nh_waste_type_existing = db.select("SELECT WasteTypeID FROM wastetype WHERE WasteTypeName = %s", ["Non-Hazardous"])
            nh_waste_type_id = nh_waste_type_existing[0]['WasteTypeID'] if nh_waste_type_existing else 1
            
            # Get or create waste categories
            waste_categories = [
                ("Hazardous Chemical Waste", "Waste from API processing and formulation", waste_type_id),
                ("Packaging Waste", "Non-hazardous packaging materials", nh_waste_type_id),
                ("Contaminated Materials", "Materials contaminated with API", waste_type_id),
                ("Powder Waste", "Waste powder from formulation", waste_type_id),
                ("Solvent Waste", "Waste solvents from cleaning", waste_type_id)
            ]
            
            waste_cat_ids = {}
            for cat_name, desc, wt_id in waste_categories:
                existing = db.select("SELECT WasteCategoryID FROM wastecategory WHERE WasteCategoryName = %s", [cat_name])
                if existing:
                    waste_cat_ids[cat_name] = existing[0]['WasteCategoryID']
                else:
                    cat_id = db.insert("wastecategory", {
                        "WasteTypeID": wt_id,
                        "WasteCategoryName": cat_name,
                        "Description": desc
                    })
                    waste_cat_ids[cat_name] = cat_id
            
            # Ensure measurement units exist
            units = ["kg", "L", "g"]
            unit_ids = {}
            for unit in units:
                existing = db.select("SELECT UnitID FROM measurementunit WHERE UnitName = %s", [unit])
                if existing:
                    unit_ids[unit] = existing[0]['UnitID']
                else:
                    unit_id = db.insert("measurementunit", {"UnitName": unit})
                    unit_ids[unit] = unit_id
            
            # Create waste records
            waste_records = [
                ("API Processing Waste", waste_cat_ids.get("Hazardous Chemical Waste", 1), 4.2, "kg", api_stage_id),
                ("Powder Waste", waste_cat_ids.get("Powder Waste", 1), 1.8, "kg", form_stage_id),
                ("Packaging Waste", waste_cat_ids.get("Packaging Waste", 1), 12.5, "kg", pack_stage_id),
                ("Contaminated Cleaning Materials", waste_cat_ids.get("Contaminated Materials", 1), 2.5, "kg", fill_stage_id),
                ("Solvent Waste", waste_cat_ids.get("Solvent Waste", 1), 6.3, "L", form_stage_id)
            ]
            
            for waste_name, cat_id, amount, unit, process_map_id in waste_records:
                try:
                    unit_id = unit_ids.get(unit)
                    
                    record_id = db.insert("wastemanagementrecord", {
                        "WasteCategoryID": cat_id,
                        "Amount": amount,
                        "UnitID": unit_id,
                        "DisposalDate": (datetime.now() + timedelta(days=30)).strftime('%Y-%m-%d'),
                        "Comments": f"Waste from DPI manufacturing process - {waste_name}"
                    })
                    report['waste_records_inserted'] += 1
                except Exception as e:
                    report['errors'].append(f"Waste record '{waste_name}': {str(e)}")
            
            print(f"  ✓ Created {report['waste_records_inserted']} waste records")
        except Exception as e:
            print(f"  ⚠ Waste records creation skipped: {str(e)}")
            report['errors'].append(f"Waste records: {str(e)}")
        
        # ===================================================================
        # FINALIZE
        # ===================================================================
        db.execute("SET FOREIGN_KEY_CHECKS = 1")
        
        print("\n" + "="*80)
        print("DPI MANUFACTURING FLOW - EXECUTION SUMMARY")
        print("="*80)
        print(f"Process Map Entries:     {report['process_map_inserted']}")
        print(f"Process Definitions:      {report['process_definitions_inserted']}")
        print(f"Process Steps:            {report['process_steps_inserted']}")
        print(f"Tasks Created:           {report['tasks_inserted']}")
        print(f"Waste Records:           {report['waste_records_inserted']}")
        print(f"7Ps Links Created:       {report['7ps_links_created']}")
        if report['errors']:
            print(f"\nErrors: {len(report['errors'])}")
            for error in report['errors'][:5]:
                print(f"  ⚠ {error}")
        print("="*80)
        print("✓ DPI Manufacturing Flow Created Successfully!")
        print("="*80)
        
        return report
        
    except Exception as e:
        db.execute("SET FOREIGN_KEY_CHECKS = 1")
        print(f"\n✗ ERROR: {str(e)}")
        import traceback
        traceback.print_exc()
        report['errors'].append(str(e))
        return report


def insert_both_flows():
    """Insert both MDI and DPI manufacturing flows"""
    print("\n" + "="*80)
    print("PHARMACEUTICAL MANUFACTURING FLOWS - MDI & DPI")
    print("="*80)
    
    mdi_report = insert_mdi_manufacturing_flow()
    dpi_report = insert_dpi_manufacturing_flow()
    
    print("\n\n" + "="*80)
    print("OVERALL SUMMARY")
    print("="*80)
    print(f"MDI Process Map Entries:  {mdi_report['process_map_inserted']}")
    print(f"DPI Process Map Entries:  {dpi_report['process_map_inserted']}")
    print(f"Total Tasks Created:      {mdi_report['tasks_inserted'] + dpi_report['tasks_inserted']}")
    print(f"Total Waste Records:      {mdi_report['waste_records_inserted'] + dpi_report['waste_records_inserted']}")
    print(f"Total 7Ps Links:          {mdi_report['7ps_links_created'] + dpi_report['7ps_links_created']}")
    print("="*80)
    print("✓ All Manufacturing Flows Created Successfully!")
    print("="*80)
    
    return {
        'mdi': mdi_report,
        'dpi': dpi_report
    }


# ---------------------------------------------------------------------------
# Main execution
# ---------------------------------------------------------------------------
if __name__ == "__main__":
    import sys
    
    if len(sys.argv) > 1:
        if sys.argv[1] == "mdi":
            insert_mdi_manufacturing_flow()
        elif sys.argv[1] == "dpi":
            insert_dpi_manufacturing_flow()
        elif sys.argv[1] == "both":
            insert_both_flows()
        else:
            print("Usage: python pharmaceutical_manufacturing_flows.py [mdi|dpi|both]")
    else:
        # Default: insert both
        insert_both_flows()

