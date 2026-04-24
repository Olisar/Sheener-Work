# File: sheener/deploy_sheener.py
#!/usr/bin/env python3
"""
SHEEner Mobile Report - Automated Deployment Script

This script automates the deployment of the SHEEner Mobile Report PWA
to a company server. It handles configuration, testing, and verification.

Usage:
    python deploy_sheener.py --ip 192.168.1.100
    python deploy_sheener.py --domain sheener.amneal.com --https
    python deploy_sheener.py --check-only

Author: SHEEner Development Team
Version: 1.0
Date: December 2025
"""

import os
import sys
import json
import re
import subprocess
import argparse
import socket
import urllib.request
import urllib.error
from pathlib import Path
from datetime import datetime
import shutil

# ANSI color codes for terminal output
class Colors:
    HEADER = '\033[95m'
    OKBLUE = '\033[94m'
    OKCYAN = '\033[96m'
    OKGREEN = '\033[92m'
    WARNING = '\033[93m'
    FAIL = '\033[91m'
    ENDC = '\033[0m'
    BOLD = '\033[1m'
    UNDERLINE = '\033[4m'

def print_header(message):
    """Print a formatted header"""
    print(f"\n{Colors.HEADER}{Colors.BOLD}{'='*60}{Colors.ENDC}")
    print(f"{Colors.HEADER}{Colors.BOLD}{message.center(60)}{Colors.ENDC}")
    print(f"{Colors.HEADER}{Colors.BOLD}{'='*60}{Colors.ENDC}\n")

def print_success(message):
    """Print a success message"""
    print(f"{Colors.OKGREEN}✓ {message}{Colors.ENDC}")

def print_error(message):
    """Print an error message"""
    print(f"{Colors.FAIL}✗ {message}{Colors.ENDC}")

def print_warning(message):
    """Print a warning message"""
    print(f"{Colors.WARNING}⚠ {message}{Colors.ENDC}")

def print_info(message):
    """Print an info message"""
    print(f"{Colors.OKCYAN}ℹ {message}{Colors.ENDC}")

class SHEEnerDeployer:
    """Main deployment class"""
    
    def __init__(self, args):
        self.args = args
        self.base_path = Path(args.path) if args.path else Path.cwd()
        self.server_ip = args.ip
        self.domain = args.domain
        self.use_https = args.https
        self.db_host = args.db_host
        self.db_name = args.db_name
        self.db_user = args.db_user
        self.db_pass = args.db_pass
        self.errors = []
        self.warnings = []
        
    def run(self):
        """Main deployment workflow"""
        print_header("SHEEner Mobile Report - Automated Deployment")
        
        if self.args.check_only:
            return self.run_checks_only()
        
        # Step 1: Pre-deployment checks
        if not self.check_prerequisites():
            print_error("Prerequisites check failed. Please fix the issues above.")
            return False
        
        # Step 2: Backup existing files
        if self.args.backup:
            self.backup_existing_files()
        
        # Step 3: Update configuration files
        if not self.update_configuration():
            print_error("Configuration update failed.")
            return False
        
        # Step 4: Set permissions
        if not self.set_permissions():
            print_warning("Permission setting had issues. Please verify manually.")
        
        # Step 5: Test connectivity
        if not self.test_connectivity():
            print_warning("Connectivity tests had issues. Please verify manually.")
        
        # Step 6: Generate QR codes
        if self.args.generate_qr:
            self.generate_qr_codes()
        
        # Step 7: Run health checks
        self.run_health_checks()
        
        # Step 8: Print summary
        self.print_summary()
        
        return len(self.errors) == 0
    
    def check_prerequisites(self):
        """Check all prerequisites"""
        print_header("Checking Prerequisites")
        
        all_ok = True
        
        # Check if running as admin (Windows) or root (Linux)
        if os.name == 'nt':  # Windows
            try:
                import ctypes
                is_admin = ctypes.windll.shell32.IsUserAnAdmin()
                if is_admin:
                    print_success("Running with administrator privileges")
                else:
                    print_warning("Not running as administrator. Some operations may fail.")
                    self.warnings.append("Not running as administrator")
            except:
                print_warning("Could not check admin status")
        else:  # Linux/Unix
            if os.geteuid() == 0:
                print_success("Running as root")
            else:
                print_warning("Not running as root. Some operations may fail.")
                self.warnings.append("Not running as root")
        
        # Check if base path exists
        if self.base_path.exists():
            print_success(f"Base path exists: {self.base_path}")
        else:
            print_error(f"Base path does not exist: {self.base_path}")
            self.errors.append(f"Base path not found: {self.base_path}")
            all_ok = False
        
        # Check for required files
        required_files = [
            'mobile_report.php',
            'manifest.json',
            'service-worker.js',
            'qr_generator.php'
        ]
        
        for file in required_files:
            file_path = self.base_path / file
            if file_path.exists():
                print_success(f"Found: {file}")
            else:
                print_error(f"Missing: {file}")
                self.errors.append(f"Required file missing: {file}")
                all_ok = False
        
        # Check for required directories
        required_dirs = [
            'img/icons',
            'js',
            'php'
        ]
        
        for dir_path in required_dirs:
            full_path = self.base_path / dir_path
            if full_path.exists():
                print_success(f"Found directory: {dir_path}")
            else:
                print_error(f"Missing directory: {dir_path}")
                self.errors.append(f"Required directory missing: {dir_path}")
                all_ok = False
        
        # Check PHP
        if self.check_command('php --version'):
            print_success("PHP is installed")
        else:
            print_error("PHP is not installed or not in PATH")
            self.errors.append("PHP not found")
            all_ok = False
        
        # Check Apache/Web server
        if os.name == 'nt':
            # Windows - check for Apache or IIS
            if self.check_process('httpd.exe') or self.check_process('apache.exe'):
                print_success("Apache is running")
            elif self.check_process('w3wp.exe'):
                print_success("IIS is running")
            else:
                print_warning("Web server not detected. Please ensure Apache or IIS is running.")
                self.warnings.append("Web server not detected")
        else:
            # Linux - check Apache
            if self.check_command('systemctl is-active apache2') or self.check_command('systemctl is-active httpd'):
                print_success("Apache is running")
            else:
                print_warning("Apache may not be running")
                self.warnings.append("Apache status unknown")
        
        # Check MySQL
        if self.check_command('mysql --version'):
            print_success("MySQL client is installed")
        else:
            print_warning("MySQL client not found in PATH")
            self.warnings.append("MySQL client not found")
        
        # Test database connection if credentials provided
        if self.db_host and self.db_name and self.db_user:
            if self.test_database_connection():
                print_success("Database connection successful")
            else:
                print_error("Database connection failed")
                self.errors.append("Database connection failed")
                all_ok = False
        
        return all_ok
    
    def check_command(self, command):
        """Check if a command executes successfully"""
        try:
            result = subprocess.run(
                command,
                shell=True,
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                timeout=5
            )
            return result.returncode == 0
        except:
            return False
    
    def check_process(self, process_name):
        """Check if a process is running (Windows)"""
        try:
            result = subprocess.run(
                f'tasklist /FI "IMAGENAME eq {process_name}"',
                shell=True,
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                timeout=5
            )
            return process_name.lower() in result.stdout.decode().lower()
        except:
            return False
    
    def test_database_connection(self):
        """Test database connection"""
        try:
            # Try using mysql command
            cmd = f'mysql -h {self.db_host} -u {self.db_user} -p{self.db_pass} -e "USE {self.db_name};"'
            result = subprocess.run(
                cmd,
                shell=True,
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                timeout=10
            )
            return result.returncode == 0
        except:
            return False
    
    def backup_existing_files(self):
        """Backup existing files"""
        print_header("Backing Up Existing Files")
        
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        backup_dir = self.base_path.parent / f"sheener_backup_{timestamp}"
        
        try:
            if self.base_path.exists():
                shutil.copytree(self.base_path, backup_dir)
                print_success(f"Backup created: {backup_dir}")
            else:
                print_info("No existing files to backup")
        except Exception as e:
            print_error(f"Backup failed: {e}")
            self.errors.append(f"Backup failed: {e}")
    
    def update_configuration(self):
        """Update configuration files with server details"""
        print_header("Updating Configuration Files")
        
        all_ok = True
        
        # Determine the base URL
        if self.domain:
            protocol = 'https' if self.use_https else 'http'
            base_url = f"{protocol}://{self.domain}"
        elif self.server_ip:
            protocol = 'https' if self.use_https else 'http'
            base_url = f"{protocol}://{self.server_ip}"
        else:
            print_error("No server IP or domain provided")
            return False
        
        print_info(f"Base URL: {base_url}")
        
        # Update manifest.json
        if not self.update_manifest(base_url):
            all_ok = False
        
        # Update service-worker.js
        if not self.update_service_worker(base_url):
            all_ok = False
        
        # Update database config if provided
        if self.db_host and self.db_name and self.db_user:
            if not self.update_database_config():
                all_ok = False
        
        return all_ok
    
    def update_manifest(self, base_url):
        """Update manifest.json"""
        manifest_path = self.base_path / 'manifest.json'
        
        try:
            with open(manifest_path, 'r', encoding='utf-8') as f:
                manifest = json.load(f)
            
            # Update start_url and scope
            manifest['start_url'] = '/sheener/mobile_report.php'
            manifest['scope'] = '/sheener/'
            
            # Save updated manifest
            with open(manifest_path, 'w', encoding='utf-8') as f:
                json.dump(manifest, f, indent=2)
            
            print_success("Updated manifest.json")
            return True
        except Exception as e:
            print_error(f"Failed to update manifest.json: {e}")
            self.errors.append(f"manifest.json update failed: {e}")
            return False
    
    def update_service_worker(self, base_url):
        """Update service-worker.js"""
        sw_path = self.base_path / 'service-worker.js'
        
        try:
            with open(sw_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            # Update cache name with timestamp
            timestamp = datetime.now().strftime('%Y%m%d')
            content = re.sub(
                r"const CACHE_NAME = '[^']*';",
                f"const CACHE_NAME = 'sheener-v{timestamp}';",
                content
            )
            
            # Save updated service worker
            with open(sw_path, 'w', encoding='utf-8') as f:
                f.write(content)
            
            print_success("Updated service-worker.js")
            return True
        except Exception as e:
            print_error(f"Failed to update service-worker.js: {e}")
            self.errors.append(f"service-worker.js update failed: {e}")
            return False
    
    def update_database_config(self):
        """Update database configuration"""
        config_files = [
            self.base_path / 'php' / 'config.php',
            self.base_path / 'php' / 'submit_anonymous_event.php'
        ]
        
        for config_file in config_files:
            if not config_file.exists():
                continue
            
            try:
                with open(config_file, 'r', encoding='utf-8') as f:
                    content = f.read()
                
                # Update database credentials
                content = re.sub(
                    r'\$db_host\s*=\s*["\'][^"\']*["\'];',
                    f'$db_host = "{self.db_host}";',
                    content
                )
                content = re.sub(
                    r'\$db_name\s*=\s*["\'][^"\']*["\'];',
                    f'$db_name = "{self.db_name}";',
                    content
                )
                content = re.sub(
                    r'\$db_user\s*=\s*["\'][^"\']*["\'];',
                    f'$db_user = "{self.db_user}";',
                    content
                )
                if self.db_pass:
                    content = re.sub(
                        r'\$db_pass\s*=\s*["\'][^"\']*["\'];',
                        f'$db_pass = "{self.db_pass}";',
                        content
                    )
                
                with open(config_file, 'w', encoding='utf-8') as f:
                    f.write(content)
                
                print_success(f"Updated {config_file.name}")
            except Exception as e:
                print_error(f"Failed to update {config_file.name}: {e}")
                self.errors.append(f"{config_file.name} update failed: {e}")
                return False
        
        return True
    
    def set_permissions(self):
        """Set file permissions"""
        print_header("Setting File Permissions")
        
        if os.name == 'nt':
            print_info("On Windows, permissions are managed through NTFS. Please verify manually.")
            return True
        
        # Linux permissions
        try:
            # Set directory permissions
            subprocess.run(['chmod', '-R', '755', str(self.base_path)], check=True)
            print_success("Set directory permissions to 755")
            
            # Set upload directory permissions if it exists
            upload_dir = self.base_path / 'uploads'
            if upload_dir.exists():
                subprocess.run(['chmod', '-R', '775', str(upload_dir)], check=True)
                print_success("Set upload directory permissions to 775")
            
            return True
        except Exception as e:
            print_error(f"Failed to set permissions: {e}")
            self.errors.append(f"Permission setting failed: {e}")
            return False
    
    def test_connectivity(self):
        """Test server connectivity"""
        print_header("Testing Connectivity")
        
        # Determine test URL
        if self.domain:
            protocol = 'https' if self.use_https else 'http'
            test_url = f"{protocol}://{self.domain}/sheener/mobile_report.php"
        elif self.server_ip:
            protocol = 'https' if self.use_https else 'http'
            test_url = f"{protocol}://{self.server_ip}/sheener/mobile_report.php"
        else:
            print_warning("No URL to test")
            return False
        
        print_info(f"Testing: {test_url}")
        
        try:
            response = urllib.request.urlopen(test_url, timeout=10)
            if response.status == 200:
                print_success("Server is accessible")
                return True
            else:
                print_warning(f"Server returned status: {response.status}")
                return False
        except urllib.error.URLError as e:
            print_error(f"Connection failed: {e}")
            self.errors.append(f"Connectivity test failed: {e}")
            return False
        except Exception as e:
            print_error(f"Unexpected error: {e}")
            self.errors.append(f"Connectivity test error: {e}")
            return False
    
    def generate_qr_codes(self):
        """Generate QR codes"""
        print_header("Generating QR Codes")
        
        try:
            import qrcode
            
            # Determine URL
            if self.domain:
                protocol = 'https' if self.use_https else 'http'
                url = f"{protocol}://{self.domain}/sheener/mobile_report.php"
            elif self.server_ip:
                protocol = 'https' if self.use_https else 'http'
                url = f"{protocol}://{self.server_ip}/sheener/mobile_report.php"
            else:
                print_warning("No URL for QR code")
                return
            
            # Generate QR code
            qr = qrcode.QRCode(version=1, box_size=10, border=5)
            qr.add_data(url)
            qr.make(fit=True)
            
            img = qr.make_image(fill_color="black", back_color="white")
            
            # Save QR code
            qr_path = self.base_path / 'qr_code.png'
            img.save(qr_path)
            
            print_success(f"QR code saved: {qr_path}")
            print_info(f"QR code URL: {url}")
        except ImportError:
            print_warning("qrcode library not installed. Run: pip install qrcode[pil]")
            self.warnings.append("QR code generation skipped - library not installed")
        except Exception as e:
            print_error(f"QR code generation failed: {e}")
            self.errors.append(f"QR code generation failed: {e}")
    
    def run_health_checks(self):
        """Run health checks"""
        print_header("Running Health Checks")
        
        checks = [
            ("PWA Icons", self.check_pwa_icons),
            ("Service Worker", self.check_service_worker),
            ("Manifest", self.check_manifest),
            ("PHP Files", self.check_php_files),
            ("JavaScript Files", self.check_js_files)
        ]
        
        for check_name, check_func in checks:
            try:
                if check_func():
                    print_success(f"{check_name}: OK")
                else:
                    print_warning(f"{check_name}: Issues found")
            except Exception as e:
                print_error(f"{check_name}: Error - {e}")
    
    def check_pwa_icons(self):
        """Check PWA icons exist"""
        icon_192 = self.base_path / 'img' / 'icons' / 'icon-192x192.png'
        icon_512 = self.base_path / 'img' / 'icons' / 'icon-512x512.png'
        return icon_192.exists() and icon_512.exists()
    
    def check_service_worker(self):
        """Check service worker file"""
        sw_path = self.base_path / 'service-worker.js'
        return sw_path.exists() and sw_path.stat().st_size > 0
    
    def check_manifest(self):
        """Check manifest file"""
        manifest_path = self.base_path / 'manifest.json'
        if not manifest_path.exists():
            return False
        try:
            with open(manifest_path, 'r') as f:
                json.load(f)
            return True
        except:
            return False
    
    def check_php_files(self):
        """Check PHP files exist"""
        required_php = ['mobile_report.php', 'qr_generator.php']
        return all((self.base_path / f).exists() for f in required_php)
    
    def check_js_files(self):
        """Check JavaScript files exist"""
        js_dir = self.base_path / 'js'
        if not js_dir.exists():
            return False
        required_js = ['offline-storage.js', 'sync-manager.js']
        return all((js_dir / f).exists() for f in required_js)
    
    def run_checks_only(self):
        """Run checks without deployment"""
        print_header("Running Pre-Deployment Checks Only")
        
        self.check_prerequisites()
        self.run_health_checks()
        self.print_summary()
        
        return len(self.errors) == 0
    
    def print_summary(self):
        """Print deployment summary"""
        print_header("Deployment Summary")
        
        if len(self.errors) == 0 and len(self.warnings) == 0:
            print_success("Deployment completed successfully with no issues!")
        elif len(self.errors) == 0:
            print_success(f"Deployment completed with {len(self.warnings)} warning(s)")
        else:
            print_error(f"Deployment completed with {len(self.errors)} error(s) and {len(self.warnings)} warning(s)")
        
        if self.warnings:
            print(f"\n{Colors.WARNING}Warnings:{Colors.ENDC}")
            for warning in self.warnings:
                print(f"  ⚠ {warning}")
        
        if self.errors:
            print(f"\n{Colors.FAIL}Errors:{Colors.ENDC}")
            for error in self.errors:
                print(f"  ✗ {error}")
        
        # Print next steps
        print(f"\n{Colors.OKBLUE}{Colors.BOLD}Next Steps:{Colors.ENDC}")
        if len(self.errors) == 0:
            if self.domain:
                protocol = 'https' if self.use_https else 'http'
                url = f"{protocol}://{self.domain}/sheener/mobile_report.php"
            elif self.server_ip:
                protocol = 'https' if self.use_https else 'http'
                url = f"{protocol}://{self.server_ip}/sheener/mobile_report.php"
            else:
                url = "http://your-server/sheener/mobile_report.php"
            
            print(f"  1. Test the application: {url}")
            print(f"  2. Generate QR codes: {url.replace('mobile_report.php', 'qr_generator.php')}")
            print(f"  3. Distribute QR codes to users")
            print(f"  4. Monitor logs for issues")
        else:
            print(f"  1. Fix the errors listed above")
            print(f"  2. Run the deployment script again")
            print(f"  3. Check the documentation in README.md")

def main():
    """Main entry point"""
    parser = argparse.ArgumentParser(
        description='Automated deployment script for SHEEner Mobile Report PWA',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog='''
Examples:
  # Deploy with IP address
  python deploy_sheener.py --ip 192.168.1.100
  
  # Deploy with domain and HTTPS
  python deploy_sheener.py --domain sheener.amneal.com --https
  
  # Deploy with database configuration
  python deploy_sheener.py --ip 192.168.1.100 --db-host localhost --db-name sheener_db --db-user sheener_user --db-pass password123
  
  # Run checks only
  python deploy_sheener.py --check-only
  
  # Deploy with backup
  python deploy_sheener.py --ip 192.168.1.100 --backup --generate-qr
        '''
    )
    
    parser.add_argument('--ip', help='Server IP address (e.g., 192.168.1.100)')
    parser.add_argument('--domain', help='Server domain name (e.g., sheener.amneal.com)')
    parser.add_argument('--https', action='store_true', help='Use HTTPS')
    parser.add_argument('--path', help='Path to sheener directory (default: current directory)')
    parser.add_argument('--db-host', help='Database host (default: localhost)', default='localhost')
    parser.add_argument('--db-name', help='Database name')
    parser.add_argument('--db-user', help='Database username')
    parser.add_argument('--db-pass', help='Database password')
    parser.add_argument('--backup', action='store_true', help='Backup existing files before deployment')
    parser.add_argument('--generate-qr', action='store_true', help='Generate QR code')
    parser.add_argument('--check-only', action='store_true', help='Run checks only, do not deploy')
    
    args = parser.parse_args()
    
    # Validate arguments
    if not args.check_only and not args.ip and not args.domain:
        parser.error("Either --ip or --domain must be specified (or use --check-only)")
    
    # Create deployer and run
    deployer = SHEEnerDeployer(args)
    success = deployer.run()
    
    sys.exit(0 if success else 1)

if __name__ == '__main__':
    main()
