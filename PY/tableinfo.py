# File: sheener/PY/tableinfo.py
import mysql.connector
import json

# Database connection details
config = {
    'user': 'root',
    'password': '',
    'host': 'localhost',
    'database': 'sheener',
    'raise_on_warnings': True
}

SELECTED_TABLES = [
    "document_access_logs",
    "document_types",
    "documents",
    "documentstatuses",
    "documenttags",
    "documentversions",
    "people",
    "people_departments",
    "people_roles",
        "quizzes",              # Links documents to training modules
    "quiz_questions",       # Questions for each quiz
    "question_options",     # Answer choices
    "quiz_attempts",
    "training_assignments",
    "trainingattempts",
    "user_agents",
    "users",
    "attempt_status",
    "change_requests",
    "changecontrol",
    "tasks",
    "task_status_log",
]

def fetch_table_structure(cursor, tables):
    table_details = {}
    for table in tables:
        try:
            cursor.execute(f"DESCRIBE `{table}`;")
            columns = [
                {
                    "name": col[0],
                    "type": col[1],
                    "null": col[2],
                    "key": col[3],
                    "default": col[4],
                    "extra": col[5]
                }
                for col in cursor.fetchall()
            ]
            cursor.execute(f"SELECT COUNT(*) FROM `{table}`;")
            row_count = cursor.fetchone()[0]
            table_details[table] = {
                "columns": columns,
                "row_count": row_count
            }
        except mysql.connector.Error as err:
            print(f"[WARN] Could not fetch structure for table `{table}`: {err}")
    return table_details

def fetch_table_data(cursor, tables):
    table_data = {}
    for table in tables:
        try:
            cursor.execute(f"SELECT * FROM `{table}`;")
            rows = cursor.fetchall()
            col_names = [desc[0] for desc in cursor.description]
            table_data[table] = [
                dict(zip(col_names, row))
                for row in rows
            ]
        except mysql.connector.Error as err:
            print(f"[WARN] Could not fetch data for table `{table}`: {err}")
    return table_data

def fetch_foreign_keys(cursor, selected_tables):
    selected_set = set(selected_tables)
    cursor.execute(
        """
        SELECT 
            TABLE_NAME, COLUMN_NAME, 
            REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM 
            information_schema.KEY_COLUMN_USAGE
        WHERE 
            TABLE_SCHEMA = DATABASE()
            AND REFERENCED_TABLE_NAME IS NOT NULL;
        """
    )
    all_fks = cursor.fetchall()
    # Only relationships where BOTH tables are in selected_tables
    filtered_fks = [
        fk for fk in all_fks
        if fk[0] in selected_set and fk[2] in selected_set
    ]
    return filtered_fks

def build_json(table_structures, table_data, foreign_keys, selected_tables):
    tables_json = []
    for table in selected_tables:
        structures = table_structures.get(table, {})
        data = table_data.get(table, [])
        tables_json.append({
            "name": table,
            "structure": structures,
            "data": data
        })
    relationships_json = [
        {
            "source": {"table": fk[0], "column": fk[1]},
            "target": {"table": fk[2], "column": fk[3]}
        }
        for fk in foreign_keys
    ]
    return {
        "tables": tables_json,
        "relationships": relationships_json
    }

def main():
    if not SELECTED_TABLES:
        print("No tables selected. Please fill SELECTED_TABLES list first.")
        return
    try:
        print("[INFO] Connecting to database...")
        conn = mysql.connector.connect(**config)
        cursor = conn.cursor()
        print("[INFO] Fetching table structures...")
        table_structures = fetch_table_structure(cursor, SELECTED_TABLES)
        print("[INFO] Fetching table data...")
        table_data = fetch_table_data(cursor, SELECTED_TABLES)
        print("[INFO] Fetching foreign key relationships...")
        foreign_keys = fetch_foreign_keys(cursor, SELECTED_TABLES)
        print("[INFO] Building export JSON...")
        db_export = build_json(table_structures, table_data, foreign_keys, SELECTED_TABLES)
        with open("py/tableinfo.json", "w", encoding="utf-8") as f:
            json.dump(db_export, f, indent=4, default=str)
        print("Selected tables exported to tableinfoschema.json")
    except mysql.connector.Error as err:
        with open("error_log.txt", "a", encoding="utf-8") as log:
            log.write(f"Error: {err}\n")
        print(f"Error logged to error_log.txt: {err}")
    finally:
        try:
            cursor.close()
        except Exception:
            pass
        try:
            conn.close()
        except Exception:
            pass

if __name__ == "__main__":
    main()
