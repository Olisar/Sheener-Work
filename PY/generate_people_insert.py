# File: sheener/PY/generate_people_insert.py
#!/usr/bin/env python3
import math
import os
import pandas as pd

# ---------------- CONFIGURATION ----------------

# Get the directory where this script is located
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
EXCEL_FILE = os.path.join(SCRIPT_DIR, "7ps list.xlsx")

PEOPLE_SHEET_NAME = "People"  # change if the sheet has a different name
OUTPUT_SQL_FILE = "people_insert.sql"
BATCH_SIZE = 50  # number of rows per INSERT

# DB table and columns (excluding auto-increment primary key)
TABLE_NAME = "people"
DB_COLUMNS = [
    "FirstName",
    "LastName",
    "DateOfBirth",
    "Email",
    "PhoneNumber",
    "Position",
    "company_id",
    "department_id",
    "IsActive",
]

# Company mapping rule: very simple example
def map_company_id(row: pd.Series) -> str:
    """
    Returns a string representing company_id value for SQL (e.g. '1' or 'NULL').
    Currently:
      - if ContractorCompID or CreatedByID contains 'AMNEAL', return '1'
      - otherwise 'NULL'
    """
    contractor_comp = str(row.get("ContractorCompID", "") or "").upper()
    created_by = str(row.get("CreatedByID", "") or "").upper()
    if "AMNEAL" in contractor_comp or "AMNEAL" in created_by:
        return "1"
    return "NULL"

# Department mapping rule (stub, returns NULL)
def map_department_id(row: pd.Series) -> str:
    """
    Placeholder for department mapping using DpetID or other columns.
    For now always returns 'NULL'.
    """
    # Example: dpet = str(row.get("DpetID", "") or "")
    return "NULL"

# IsActive rule: 1 if FirstName non-empty, else 0
def map_is_active(row: pd.Series) -> str:
    first_name = str(row.get("FirstName", "") or "").strip()
    return "1" if first_name else "0"

# ---------------- HELPER FUNCTIONS ----------------

def sql_escape(value: str) -> str:
    """
    Escape single quotes for SQL and wrap in quotes.
    If value is empty or None, return 'NULL' without quotes.
    """
    if value is None:
        return "NULL"
    value = str(value)
    if value == "" or value.upper() == "NAN":
        return "NULL"
    # escape single quotes
    value = value.replace("'", "''")
    return f"'{value}'"

def build_values_row(row: pd.Series) -> tuple[str, bool]:
    """
    Build a single SQL VALUES tuple for the given row.
    DateOfBirth and PhoneNumber are written as NULL (or you can adjust later).
    Returns: (values_string, is_valid) - is_valid is False if required fields are missing
    """
    first_name_raw = str(row.get("FirstName", "") or "").strip()
    last_name_raw = str(row.get("SurName", "") or "").strip()
    email_raw = str(row.get("Email", "") or "").strip()
    
    # Check required fields (FirstName and LastName are NOT NULL in DB)
    # Email can be NULL based on existing data patterns
    if not first_name_raw or not last_name_raw:
        return ("", False)  # Invalid row - missing required fields
    
    first_name = sql_escape(first_name_raw)
    last_name = sql_escape(last_name_raw)
    email = sql_escape(email_raw)
    position = sql_escape(str(row.get("Position", "") or "").strip())

    # No DOB and Phone in source: use NULL
    date_of_birth = "NULL"
    phone_number = "NULL"

    company_id = map_company_id(row)
    department_id = map_department_id(row)
    is_active = map_is_active(row)

    values = [
        first_name,
        last_name,
        date_of_birth,
        email,
        phone_number,
        position,
        company_id,
        department_id,
        is_active,
    ]
    return ("(" + ", ".join(values) + ")", True)

# ---------------- MAIN PROCESS ----------------

def main():
    if not os.path.exists(EXCEL_FILE):
        raise FileNotFoundError(f"Excel file '{EXCEL_FILE}' not found in current directory.")

    # Read People sheet
    df = pd.read_excel(EXCEL_FILE, sheet_name=PEOPLE_SHEET_NAME)

    # Basic clean-up: drop completely empty rows on key columns
    df = df[(df["FirstName"].notna()) | (df["SurName"].notna())]

    total_rows = len(df)
    print(f"Loaded {total_rows} rows from sheet '{PEOPLE_SHEET_NAME}'.")

    all_inserts = []
    skipped_rows = 0
    valid_rows = 0

    num_batches = math.ceil(total_rows / BATCH_SIZE)

    for batch_index in range(num_batches):
        start = batch_index * BATCH_SIZE
        end = min(start + BATCH_SIZE, total_rows)
        batch_df = df.iloc[start:end]

        values_rows = []
        for _, row in batch_df.iterrows():
            values_str, is_valid = build_values_row(row)
            if is_valid:
                values_rows.append(values_str)
                valid_rows += 1
            else:
                skipped_rows += 1

        if not values_rows:
            continue

        insert_header = (
            f"INSERT INTO {TABLE_NAME} "
            f"({', '.join(DB_COLUMNS)})\nVALUES\n"
        )
        insert_body = ",\n".join(values_rows) + ";\n"
        all_inserts.append(insert_header + insert_body)

    sql_script = "\n\n".join(all_inserts)

    output_path = os.path.join(SCRIPT_DIR, OUTPUT_SQL_FILE)
    with open(output_path, "w", encoding="utf-8") as f:
        f.write(sql_script)

    print(f"Written SQL script with {valid_rows} valid rows into '{output_path}' in {num_batches} batches.")
    if skipped_rows > 0:
        print(f"Warning: {skipped_rows} rows were skipped due to missing required fields (FirstName, LastName, or Email).")

if __name__ == "__main__":
    main()
