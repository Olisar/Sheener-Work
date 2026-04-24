import mysql.connector
import json
#sheener/test_node/extDBMap.py
# Database connection details
config = {
    'user': 'root',
    'password': '',
    'host': 'localhost',
    'database': 'sheener',
    'raise_on_warnings': True
}


def fetch_table_metadata(cursor):
    """Fetch details of all tables including columns, primary keys, and row count."""
    cursor.execute("SHOW TABLES;")
    tables = [table[0] for table in cursor.fetchall()]

    table_details = {}
    for table in tables:
        # Fetch column details
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

        # Fetch row count
        cursor.execute(f"SELECT COUNT(*) FROM `{table}`;")
        row_count = cursor.fetchone()[0]

        table_details[table] = {
            "columns": columns,
            "row_count": row_count
        }

    return table_details


def fetch_foreign_keys(cursor):
    """Fetch foreign key relationships."""
    cursor.execute(
        """
        SELECT 
            TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM 
            information_schema.KEY_COLUMN_USAGE
        WHERE 
            TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL;
        """
    )
    return cursor.fetchall()


def generate_json_structure(table_details, foreign_keys):
    """Generate JSON structure with nodes and links."""
    nodes = []
    links = []

    for table, details in table_details.items():
        nodes.append({
            "id": table,
            "columns": details["columns"],
            "row_count": details["row_count"]
        })

    for fk in foreign_keys:
        links.append({
            "source": {
                "table": fk[0],
                "column": fk[1]
            },
            "target": {
                "table": fk[2],
                "column": fk[3]
            }
        })

    return {"nodes": nodes, "links": links}


def main():
    conn = None
    cursor = None

    try:
        conn = mysql.connector.connect(**config)
        cursor = conn.cursor()

        # Fetch metadata and relationships
        table_details = fetch_table_metadata(cursor)
        foreign_keys = fetch_foreign_keys(cursor)

        # Generate JSON structure
        db_structure = generate_json_structure(table_details, foreign_keys)

        # Write to JSON file
        with open("db_schema.json", "w") as f:
            json.dump(db_structure, f, indent=4)

        print("Database schema saved to db_schema.json")

    except mysql.connector.Error as err:
        with open("error_log.txt", "a") as log:
            log.write(f"Error: {err}\n")
        print(f"Error logged to error_log.txt: {err}")

    finally:
        if cursor is not None:
            cursor.close()
        if conn is not None and conn.is_connected():
            conn.close()


if __name__ == "__main__":
    main()
