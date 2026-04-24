# File: sheener/PY/schema7Ps.py
import mysql.connector
import json
import os

# DB connection settings
DB_CONFIG = {
    'user': 'root',
    'password': '',
    'host': 'localhost',
    'database': '7Ps',
}

def get_schema_details(db_config):
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor(dictionary=True)

    db_name = db_config['database']

    # --- Get table list ---
    cursor.execute("SHOW TABLES;")
    tables = [list(row.values())[0] for row in cursor.fetchall()]

    # --- Get table-level meta (engine, rows, comment) in one shot ---
    cursor.execute("""
        SELECT TABLE_NAME, ENGINE, TABLE_ROWS, TABLE_COMMENT
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = %s;
    """, (db_name,))
    meta_rows = cursor.fetchall()
    table_meta = {
        r['TABLE_NAME']: {
            'engine': r['ENGINE'],
            'row_count': r['TABLE_ROWS'],
            'comment': r['TABLE_COMMENT'],
        }
        for r in meta_rows
    }

    schema = {}

    for table in tables:
        # --- Columns ---
        cursor.execute(f"DESCRIBE `{table}`;")
        columns = cursor.fetchall()

        # Primary key list for convenience
        primary_key = [col['Field'] for col in columns if col['Key'] == 'PRI']

        # --- Foreign keys with referential actions ---
        cursor.execute("""
            SELECT
                k.CONSTRAINT_NAME,
                k.COLUMN_NAME,
                k.REFERENCED_TABLE_NAME,
                k.REFERENCED_COLUMN_NAME,
                r.UPDATE_RULE,
                r.DELETE_RULE
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
            JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS r
              ON k.CONSTRAINT_NAME = r.CONSTRAINT_NAME
             AND k.CONSTRAINT_SCHEMA = r.CONSTRAINT_SCHEMA
            WHERE k.TABLE_SCHEMA = %s
              AND k.TABLE_NAME = %s
              AND k.REFERENCED_TABLE_NAME IS NOT NULL;
        """, (db_name, table))
        fks = cursor.fetchall()

        # --- Indexes ---
        cursor.execute(f"SHOW INDEX FROM `{table}`;")
        index_rows = cursor.fetchall()
        # Keep as-is (MySQL structure) – good enough for D3 / debugging
        indexes = index_rows

        schema[table] = {
            'meta': table_meta.get(table, {}),
            'columns': columns,        # unchanged
            'primary_key': primary_key,
            'foreign_keys': fks,       # enriched vs. original
            'indexes': indexes,
            # referenced_by will be filled in second pass
            'referenced_by': []
        }

    # --- Build reverse relationships (who references whom) ---
    for table, info in schema.items():
        for fk in info.get('foreign_keys', []):
            ref_table = fk.get('REFERENCED_TABLE_NAME')
            if ref_table and ref_table in schema:
                schema[ref_table]['referenced_by'].append({
                    'table': table,
                    'column': fk.get('COLUMN_NAME'),
                    'constraint_name': fk.get('CONSTRAINT_NAME'),
                })

    cursor.close()
    conn.close()
    return schema


schema = get_schema_details(DB_CONFIG)

# Ensure JSON writes to the same folder as the .py file
this_folder = os.path.dirname(os.path.abspath(__file__))
json_path = os.path.join(this_folder, 'schema7Ps.json')

with open(json_path, 'w') as f:
    json.dump(schema, f, indent=2)

print(f'Schema exported to {json_path}')
