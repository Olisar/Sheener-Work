# File: sheener/test_node/pdf_and_API.py
import os
import json
import mysql.connector
import openai
from datetime import datetime
import PyPDF2
import logging

# Configure logging
logging.basicConfig(
    filename="process_pdfs.log",
    level=logging.INFO,
    format="%(asctime)s - %(levelname)s - %(message)s"
)

# Load OpenAI API Key
openai.api_key = os.getenv("OPENAI_API_KEY")
if not openai.api_key:
    logging.error("OpenAI API Key not found in environment variables.")
    exit(1)

# Database connection
def get_db_connection():
    try:
        conn = mysql.connector.connect(
            host="localhost",
            user="root",
            password="",
            database="sheener"
        )
        logging.info("Database connection established.")
        return conn
    except mysql.connector.Error as e:
        logging.error(f"Database connection error: {e}")
        return None

# Extract text from PDF
def extract_text_from_pdf(pdf_path):
    try:
        with open(pdf_path, 'rb') as file:
            reader = PyPDF2.PdfReader(file)
            text = ""
            for page in reader.pages:
                text += page.extract_text()
            logging.info(f"Extracted text from PDF: {pdf_path}")
            return text
    except Exception as e:
        logging.error(f"Error extracting text from PDF {pdf_path}: {e}")
        return None

# Analyze PDF content using OpenAI API
def analyze_pdf_with_openai(pdf_content):
    try:
        completion = openai.ChatCompletion.create(
            model="gpt-4-0613",
            messages=[
                {"role": "system", "content": "Extract key SOP data in JSON format."},
                {"role": "user", "content": pdf_content}
            ]
        )
        response_content = completion["choices"][0]["message"]["content"]
        logging.info("Analyzed PDF content with OpenAI API.")
        return response_content
    except Exception as e:
        logging.error(f"Error analyzing text with OpenAI: {e}")
        return None

# Save extracted data to database
def save_to_database(cursor, sop_data):
    sql = """
    INSERT INTO sop_data (
        title, file, owner, prepared_by, reviewed_by, 
        approved_by, effective_date, review_date, 
        supersedes, related_to, key_points
    ) 
    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
    """
    data = (
        sop_data.get("Title"),
        sop_data.get("File"),
        sop_data.get("Owner"),
        sop_data.get("Prepared_By"),
        sop_data.get("Reviewed_By"),
        sop_data.get("Approved_By"),
        sop_data.get("Effective_Date"),
        sop_data.get("Review_Date"),
        sop_data.get("Supersedes"),
        sop_data.get("Related_To"),
        sop_data.get("Key_Points")
    )
    try:
        cursor.execute(sql, data)
        logging.info(f"Successfully saved SOP: {sop_data.get('Title')}")
    except mysql.connector.Error as e:
        logging.error(f"Error saving to database: {e}")

# Process PDF files in the directory
def process_pdfs(directory, cursor):
    for root, _, files in os.walk(directory):
        for file in files:
            if file.endswith(".pdf"):
                pdf_path = os.path.join(root, file)
                logging.info(f"Processing file: {file} from {directory}")

                # Extract text from PDF
                pdf_content = extract_text_from_pdf(pdf_path)
                if not pdf_content:
                    logging.error(f"Failed to extract text from {file}")
                    continue

                # Analyze PDF content using OpenAI API
                api_response = analyze_pdf_with_openai(pdf_content)
                if not api_response:
                    logging.error(f"Failed to analyze text for {file}")
                    continue

                # Parse API response and save to database
                try:
                    sop_data = json.loads(api_response)
                    sop_data["File"] = file  # Include file name
                    save_to_database(cursor, sop_data)
                except json.JSONDecodeError as e:
                    logging.error(f"Error decoding JSON for {file}: {e}")
                    logging.error(f"Response was: {api_response}")

# Main function
if __name__ == "__main__":
    # Database connection
    conn = get_db_connection()
    if not conn:
        logging.critical("Terminating script due to database connection failure.")
        exit(1)

    cursor = conn.cursor()

    # Directories to process
    directories = [
        "C:/Documents/EHS",
        "C:/Documents/ES",
        "C:/Documents/QA QC"
    ]

    # Process each directory
    for directory in directories:
        if os.path.exists(directory):
            process_pdfs(directory, cursor)
        else:
            logging.warning(f"Directory not found: {directory}")

    # Commit changes and close database connection
    conn.commit()
    cursor.close()
    conn.close()
    logging.info("Processing completed. Database connection closed.")
