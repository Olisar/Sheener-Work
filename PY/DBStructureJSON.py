# File: sheener/PY/DBStructureJSON.py
import mysql.connector
import json
import os
# DBStructureJSON.py - This script exports the database schema to a JSON file in the py folder
# Database connection credentials
host = 'localhost'
db = 'sheener'
user = 'root'
passwd = ''  # Use a secure password!

# Create py directory if it doesn't exist
output_dir = 'py'
if not os.path.exists(output_dir):
    os.makedirs(output_dir)

try:
    # Connect to the MySQL database
    conn = mysql.connector.connect(
        host=host,
        user=user,
        password=passwd,
        database=db,
        charset='utf8mb4'
    )
    cursor = conn.cursor()

    # Function to get all table names
    def get_table_names(cur):
        cur.execute("SHOW TABLES")
        return [row[0] for row in cur.fetchall()]

    # Get full CREATE TABLE statement
    def get_create_statement(cur, table):
        cur.execute(f"SHOW CREATE TABLE `{table}`")
        return cur.fetchone()[1]

    # Get DESCRIBE info
    def get_table_structure(cur, table):
        cur.execute(f"DESCRIBE `{table}`")
        return [
            {
                "Field": col[0],
                "Type": col[1],
                "Null": col[2],
                "Key": col[3],
                "Default": col[4],
                "Extra": col[5]
            }
            for col in cur.fetchall()
        ]

    # Get all foreign key relationships for a table
    def get_foreign_keys(cur, table):
        cur.execute(f"""
            SELECT 
                COLUMN_NAME, 
                REFERENCED_TABLE_NAME, 
                REFERENCED_COLUMN_NAME
            FROM 
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE 
                TABLE_SCHEMA = %s
                AND TABLE_NAME = %s
                AND REFERENCED_TABLE_NAME IS NOT NULL
        """, (db, table))
        return [
            {
                "Field": fk[0],
                "References Table": fk[1],
                "References Field": fk[2]
            }
            for fk in cur.fetchall()
        ]

    def get_views(cur):
        cur.execute("""
            SELECT TABLE_NAME 
            FROM INFORMATION_SCHEMA.VIEWS 
            WHERE TABLE_SCHEMA = %s
        """, (db,))
        return [row[0] for row in cur.fetchall()]

    def get_stored_procedures(cur):
        cur.execute("""
            SELECT ROUTINE_NAME
            FROM INFORMATION_SCHEMA.ROUTINES
            WHERE ROUTINE_SCHEMA = %s AND ROUTINE_TYPE = 'PROCEDURE'
        """, (db,))
        return [row[0] for row in cur.fetchall()]

    def get_triggers(cur):
        cur.execute("""
            SELECT TRIGGER_NAME
            FROM INFORMATION_SCHEMA.TRIGGERS
            WHERE TRIGGER_SCHEMA = %s
        """, (db,))
        return [row[0] for row in cur.fetchall()]

    # Assemble schema info
    tables = get_table_names(cursor)
    schema = {
        "Tables": {},
        "Views": get_views(cursor),
        "StoredProcedures": get_stored_procedures(cursor),
        "Triggers": get_triggers(cursor)
    }

    for table in tables:
        schema["Tables"][table] = {
            "CreateStatement": get_create_statement(cursor, table),
            "Fields": get_table_structure(cursor, table),
            "ForeignKeys": get_foreign_keys(cursor, table)
        }

    # Export schema to JSON in py folder
    json_file_path = os.path.join(output_dir, "DBStructureExport.json")
    with open(json_file_path, "w", encoding="utf8") as outfile:
        json.dump(schema, outfile, indent=4)

    # Optional: Write summary TXT for human reading (DDL/statements and relationships only)
    txt_file_path = os.path.join(output_dir, "DBStructureExport.txt")
    with open(txt_file_path, "w", encoding="utf8") as summary:
        for table in tables:
            summary.write(f"Table: {table}\n")
            summary.write(schema["Tables"][table]["CreateStatement"] + "\n\n")
            if schema["Tables"][table]["ForeignKeys"]:
                summary.write("Foreign Keys:\n")
                for fk in schema["Tables"][table]["ForeignKeys"]:
                    summary.write(f"  {fk['Field']} REFERENCES {fk['References Table']}({fk['References Field']})\n")
                summary.write("\n")

        if schema["Views"]:
            summary.write("Views:\n" + "\n".join(schema["Views"]) + "\n\n")
        if schema["StoredProcedures"]:
            summary.write("Stored Procedures:\n" + "\n".join(schema["StoredProcedures"]) + "\n\n")
        if schema["Triggers"]:
            summary.write("Triggers:\n" + "\n".join(schema["Triggers"]) + "\n\n")

    cursor.close()
    conn.close()
    print(f"Database schema exported to '{json_file_path}' and '{txt_file_path}'")

except mysql.connector.Error as err:
    print(f"Error: {err}")