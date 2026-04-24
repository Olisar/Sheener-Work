# File: sheener/PY/cleanup_duplicates.py
#!/usr/bin/env python3
"""
cleanup_duplicates.py - Removes duplicate navbar/topbar/AI Navigator divs from migrated pages
"""

import re
from pathlib import Path

BASE_DIR = Path(__file__).parent.parent

def cleanup_page(file_path):
    """Remove duplicate elements from migrated page"""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
        
        # Skip if not using includes
        if 'includes/header.php' not in content:
            return False, "Not using includes"
        
        original_content = content
        
        # Remove duplicate topbar/navbar divs after include
        # Pattern: after include, look for duplicate divs
        pattern = r'(include\s+[\'"]includes/header\.php[\'"];\s*\?>\s*)(?:\s*<div[^>]*id=["\'](?:topbar|navbar)["\'][^>]*>.*?</div>\s*)+'
        content = re.sub(pattern, r'\1', content, flags=re.DOTALL | re.IGNORECASE)
        
        # Remove duplicate AI Navigator after include
        pattern = r'(include\s+[\'"]includes/header\.php[\'"];\s*\?>\s*)(?:\s*<!--\s*AI\s+Navigator.*?</div>\s*)+'
        content = re.sub(pattern, r'\1', content, flags=re.DOTALL | re.IGNORECASE)
        
        # Remove duplicate session_start() calls
        lines = content.split('\n')
        seen_session = False
        cleaned_lines = []
        for line in lines:
            if 'session_start()' in line:
                if not seen_session:
                    cleaned_lines.append(line)
                    seen_session = True
                # Skip duplicate
            else:
                cleaned_lines.append(line)
        content = '\n'.join(cleaned_lines)
        
        # Remove duplicate variable declarations
        # This is more complex, so we'll be conservative
        # Remove duplicate $user_role, $user_id, $user_name after first occurrence
        seen_vars = set()
        lines = content.split('\n')
        cleaned_lines = []
        for line in lines:
            var_match = re.match(r'\s*\$user_(role|id|name)\s*=', line)
            if var_match:
                var_name = var_match.group(1)
                if var_name not in seen_vars:
                    cleaned_lines.append(line)
                    seen_vars.add(var_name)
                # Skip duplicate
            else:
                cleaned_lines.append(line)
        content = '\n'.join(cleaned_lines)
        
        if content != original_content:
            with open(file_path, 'w', encoding='utf-8') as f:
                f.write(content)
            return True, "Cleaned up duplicates"
        else:
            return False, "No duplicates found"
    
    except Exception as e:
        return False, f"Error: {str(e)}"

def main():
    """Main function"""
    # Find all PHP files using includes
    php_files = list(BASE_DIR.glob('*.php'))
    php_files.extend(BASE_DIR.glob('php/*.php'))
    
    migrated_files = []
    for file_path in php_files:
        try:
            with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
                if 'includes/header.php' in f.read():
                    migrated_files.append(file_path)
        except:
            continue
    
    print(f"Found {len(migrated_files)} migrated PHP files")
    print("Cleaning up duplicates...\n")
    
    success_count = 0
    no_change_count = 0
    error_count = 0
    
    for file_path in migrated_files:
        success, message = cleanup_page(file_path)
        
        if success:
            print(f"✅ {file_path.name}: {message}")
            success_count += 1
        elif "No duplicates" in message:
            no_change_count += 1
        elif "Not using includes" in message:
            no_change_count += 1
        else:
            print(f"⚠️  {file_path.name}: {message}")
            error_count += 1
    
    print(f"\n=== Cleanup Complete ===")
    print(f"✅ Cleaned: {success_count}")
    print(f"⏭️  No change needed: {no_change_count}")
    print(f"⚠️  Issues: {error_count}")

if __name__ == '__main__':
    main()

