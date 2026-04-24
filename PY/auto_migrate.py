# File: sheener/PY/auto_migrate.py
#!/usr/bin/env python3
"""
auto_migrate.py - Automatically migrates PHP pages to use includes/header.php and includes/footer.php
"""

import re
import json
from pathlib import Path

BASE_DIR = Path(__file__).parent.parent

def migrate_page(file_path):
    """Migrate a single page to use includes"""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
        
        # Skip if already migrated
        if 'includes/header.php' in content:
            return False, "Already migrated"
        
        # Extract information
        title_match = re.search(r'<title>(.*?)</title>', content, re.IGNORECASE)
        title = title_match.group(1) if title_match else 'Page'
        
        desc_match = re.search(r'<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']', content, re.IGNORECASE)
        description = desc_match.group(1) if desc_match else None
        
        keywords_match = re.search(r'<meta\s+name=["\']keywords["\']\s+content=["\'](.*?)["\']', content, re.IGNORECASE)
        keywords = keywords_match.group(1) if keywords_match else None
        
        author_match = re.search(r'<meta\s+name=["\']author["\']\s+content=["\'](.*?)["\']', content, re.IGNORECASE)
        author = author_match.group(1) if author_match else None
        
        # Check for AI Navigator
        has_ai = 'ai-navigator.js' in content or 'ai-navigator-container' in content
        
        # Find additional scripts (external or custom)
        script_matches = re.findall(r'<script[^>]*src=["\']([^"\']+)["\'][^>]*>', content, re.IGNORECASE)
        additional_scripts = []
        for script in script_matches:
            if script not in ['js/navbar.js', 'js/topbar.js', 'js/ai-navigator.js']:
                if not script.startswith('http'):
                    additional_scripts.append(script)
                else:
                    additional_scripts.append(script)
        
        # Find additional stylesheets
        stylesheet_matches = re.findall(r'<link[^>]*rel=["\']stylesheet["\'][^>]*href=["\']([^"\']+)["\']', content, re.IGNORECASE)
        additional_stylesheets = []
        for sheet in stylesheet_matches:
            if sheet not in ['css/styles.css', 'css/ai-navigator.css']:
                additional_stylesheets.append(sheet)
        
        # Check for session_start
        has_session = 'session_start()' in content
        
        # Build PHP header
        php_header = "<?php\n"
        if has_session:
            php_header += "session_start();\n"
        php_header += f'$page_title = {repr(title)};\n'
        if description:
            php_header += f'$page_description = {repr(description)};\n'
        if keywords:
            php_header += f'$page_keywords = {repr(keywords)};\n'
        if author:
            php_header += f'$page_author = {repr(author)};\n'
        php_header += f'$use_ai_navigator = {str(has_ai).lower()};\n'
        if has_session:
            php_header += "$user_role = $_SESSION['role'] ?? 'User';\n"
            php_header += "$user_id = $_SESSION['user_id'] ?? '';\n"
            php_header += "$user_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');\n"
        else:
            php_header += "$user_role = 'User';\n"
            php_header += "$user_id = '';\n"
            php_header += "$user_name = 'User';\n"
        
        if additional_scripts:
            scripts_str = ', '.join([repr(s) for s in additional_scripts])
            php_header += f'$additional_scripts = [{scripts_str}];\n'
        
        if additional_stylesheets:
            sheets_str = ', '.join([repr(s) for s in additional_stylesheets])
            php_header += f'$additional_stylesheets = [{sheets_str}];\n'
        
        php_header += "include 'includes/header.php';\n"
        php_header += "?>\n"
        
        # Remove old head section
        head_pattern = r'<!DOCTYPE\s+html[^>]*>.*?<head[^>]*>.*?</head>'
        content = re.sub(head_pattern, php_header, content, flags=re.DOTALL | re.IGNORECASE)
        
        # Remove old body opening with navbar/topbar/AI Navigator
        body_pattern = r'<body[^>]*>.*?(?:<div[^>]*id=["\'](?:topbar|navbar)["\'][^>]*>.*?</div>\s*)*.*?(?:<!--\s*AI\s+Navigator.*?</div>\s*)?'
        content = re.sub(body_pattern, '', content, flags=re.DOTALL | re.IGNORECASE)
        
        # Replace closing tags
        content = re.sub(r'</body>\s*</html>', "<?php include 'includes/footer.php'; ?>", content, flags=re.IGNORECASE)
        
        # Write back
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(content)
        
        return True, "Migrated successfully"
    
    except Exception as e:
        return False, f"Error: {str(e)}"

def main():
    """Main function"""
    candidates_file = BASE_DIR / 'py' / 'migration_candidates.json'
    
    if not candidates_file.exists():
        print("❌ migration_candidates.json not found. Run migrate_to_includes.py first.")
        return
    
    with open(candidates_file, 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    candidates = data.get('candidates', [])
    
    print(f"Found {len(candidates)} migration candidates")
    print("Starting migration...\n")
    
    success_count = 0
    error_count = 0
    skipped_count = 0
    
    for candidate in candidates:
        file_path = BASE_DIR / candidate['path']
        
        if not file_path.exists():
            print(f"⚠️  {candidate['name']}: File not found")
            error_count += 1
            continue
        
        success, message = migrate_page(file_path)
        
        if success:
            print(f"✅ {candidate['name']}: {message}")
            success_count += 1
        elif "Already migrated" in message:
            print(f"⏭️  {candidate['name']}: {message}")
            skipped_count += 1
        else:
            print(f"❌ {candidate['name']}: {message}")
            error_count += 1
    
    print(f"\n=== Migration Complete ===")
    print(f"✅ Success: {success_count}")
    print(f"⏭️  Skipped: {skipped_count}")
    print(f"❌ Errors: {error_count}")
    print(f"\nNext steps:")
    print("1. Test migrated pages in browser")
    print("2. Run: python PY/frontendschema.py")
    print("3. Run: python PY/validate_refactoring.py")

if __name__ == '__main__':
    main()

