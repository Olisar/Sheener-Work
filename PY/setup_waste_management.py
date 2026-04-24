# File: sheener/PY/setup_waste_management.py
"""
Waste Management System - Setup Script
Runs all setup tasks: creates schema, verifies dependencies, creates directories
"""

import sys
import os
import subprocess

def check_dependencies():
    """Check if required Python packages are installed"""
    print("Checking Python dependencies...")
    required_packages = {
        'mysql.connector': 'mysql-connector-python',
        'pandas': 'pandas',
        'openpyxl': 'openpyxl'
    }
    
    missing = []
    for module, package in required_packages.items():
        try:
            __import__(module)
            print(f"  ✓ {package}")
        except ImportError:
            print(f"  ✗ {package} - MISSING")
            missing.append(package)
    
    if missing:
        print(f"\nMissing packages. Install with:")
        print(f"  pip install {' '.join(missing)}")
        return False
    
    return True

def create_directories():
    """Create necessary directories"""
    print("\nCreating directories...")
    base_dir = os.path.dirname(os.path.abspath(__file__))
    project_root = os.path.dirname(base_dir)
    
    directories = [
        os.path.join(project_root, 'uploads', 'waste_management'),
        os.path.join(project_root, 'reports', 'waste_management'),
    ]
    
    for directory in directories:
        if not os.path.exists(directory):
            os.makedirs(directory, exist_ok=True)
            print(f"  ✓ Created {directory}")
        else:
            print(f"  ✓ {directory} already exists")
    
    return True

def run_schema_creation():
    """Run the schema creation script"""
    print("\nCreating database schema...")
    schema_script = os.path.join(os.path.dirname(__file__), 'waste_management_schema.py')
    
    if not os.path.exists(schema_script):
        print(f"  ✗ Schema script not found: {schema_script}")
        return False
    
    try:
        result = subprocess.run([sys.executable, schema_script], 
                              capture_output=True, text=True, timeout=60)
        if result.returncode == 0:
            print("  ✓ Database schema created successfully")
            if result.stdout:
                print(result.stdout)
            return True
        else:
            print(f"  ✗ Schema creation failed:")
            print(result.stderr)
            return False
    except Exception as e:
        print(f"  ✗ Error running schema script: {e}")
        return False

def verify_database_connection():
    """Verify database connection"""
    print("\nVerifying database connection...")
    try:
        import mysql.connector
        
        # Database connection credentials (same as in schema file)
        host = 'localhost'
        db = 'sheener'
        user = 'root'
        passwd = ''
        
        conn = mysql.connector.connect(
            host=host,
            user=user,
            password=passwd,
            database=db,
            charset='utf8mb4'
        )
        conn.close()
        print("  ✓ Database connection successful")
        return True
    except Exception as e:
        print(f"  ✗ Database connection failed: {e}")
        print("  Please check your database credentials in waste_management_schema.py")
        return False

def main():
    """Main setup function"""
    print("=" * 60)
    print("Waste Management System - Setup")
    print("=" * 60)
    
    # Check dependencies
    if not check_dependencies():
        print("\n⚠ Please install missing dependencies before continuing.")
        response = input("Continue anyway? (y/n): ")
        if response.lower() != 'y':
            sys.exit(1)
    
    # Create directories
    if not create_directories():
        print("\n✗ Failed to create directories")
        sys.exit(1)
    
    # Verify database connection
    if not verify_database_connection():
        print("\n⚠ Database connection failed. Schema creation will be skipped.")
        response = input("Continue with directory setup only? (y/n): ")
        if response.lower() != 'y':
            sys.exit(1)
        print("\n✓ Setup completed (directories only)")
        return
    
    # Create schema
    print("\n" + "=" * 60)
    response = input("Create database schema? This will create all required tables. (y/n): ")
    if response.lower() == 'y':
        if not run_schema_creation():
            print("\n⚠ Schema creation failed. You can run it manually later:")
            print("  python PY/waste_management_schema.py")
    else:
        print("Skipping schema creation. Run manually with:")
        print("  python PY/waste_management_schema.py")
    
    print("\n" + "=" * 60)
    print("Setup completed!")
    print("=" * 60)
    print("\nNext steps:")
    print("1. Access the web dashboard: waste_management_dashboard.html")
    print("2. Import your first Excel file via the upload interface")
    print("3. Generate reports to view your data")
    print("\nFor more information, see: PY/WASTE_MANAGEMENT_README.md")

if __name__ == "__main__":
    main()

