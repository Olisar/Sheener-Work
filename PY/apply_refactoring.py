# File: sheener/PY/apply_refactoring.py
#!/usr/bin/env python3
"""
apply_refactoring.py - Applies automated refactoring improvements
Run this to apply quick wins and standardizations
"""

import json
import os
import re
from pathlib import Path
from collections import defaultdict

BASE_DIR = Path(__file__).parent.parent
SCHEMA_FILE = BASE_DIR / 'py' / 'frontendschema.json'
PLAN_FILE = BASE_DIR / 'py' / 'refactoring_plan.json'

def load_json(file_path):
    """Load JSON file"""
    with open(file_path, 'r', encoding='utf-8') as f:
        return json.load(f)

def add_ai_navigator_to_page(file_path):
    """Add AI Navigator to a page file"""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
        
        # Skip if already has AI Navigator
        if 'ai-navigator' in content.lower():
            return False, "Already has AI Navigator"
        
        # Check if it's an HTML/PHP file
        if not file_path.suffix in ['.html', '.php']:
            return False, "Not an HTML/PHP file"
        
        # Find head section
        head_match = re.search(r'(<head[^>]*>.*?</head>)', content, re.DOTALL | re.IGNORECASE)
        if not head_match:
            return False, "No head section found"
        
        head_content = head_match.group(1)
        
        # Add AI Navigator CSS if not present
        if 'ai-navigator.css' not in head_content:
            # Find last stylesheet link
            stylesheet_pattern = r'(<link[^>]*rel=["\']stylesheet["\'][^>]*>)'
            stylesheets = list(re.finditer(stylesheet_pattern, head_content, re.IGNORECASE))
            if stylesheets:
                last_stylesheet = stylesheets[-1]
                insert_pos = last_stylesheet.end()
                head_content = (head_content[:insert_pos] + 
                               '\n    <link rel="stylesheet" href="css/ai-navigator.css">' +
                               head_content[insert_pos:])
            else:
                # Insert after title
                title_match = re.search(r'(<title>.*?</title>)', head_content, re.IGNORECASE)
                if title_match:
                    insert_pos = title_match.end()
                    head_content = (head_content[:insert_pos] + 
                                   '\n    <link rel="stylesheet" href="css/ai-navigator.css">' +
                                   head_content[insert_pos:])
        
        # Add AI Navigator JS if not present
        if 'ai-navigator.js' not in head_content:
            # Find last script tag
            script_pattern = r'(<script[^>]*src=["\'][^"\']*["\'][^>]*>)'
            scripts = list(re.finditer(script_pattern, head_content, re.IGNORECASE))
            if scripts:
                last_script = scripts[-1]
                insert_pos = last_script.end()
                head_content = (head_content[:insert_pos] + 
                               '\n    <script src="js/ai-navigator.js" defer></script>' +
                               head_content[insert_pos:])
        
        # Update head in content
        content = content.replace(head_match.group(1), head_content)
        
        # Add AI Navigator container in body
        body_match = re.search(r'(<body[^>]*>)(.*?)(</body>)', content, re.DOTALL | re.IGNORECASE)
        if body_match:
            body_start = body_match.group(1)
            body_content = body_match.group(2)
            body_end = body_match.group(3)
            
            # Check if navbar/topbar exists
            if 'id="topbar"' in body_content or 'id="navbar"' in body_content:
                # Insert after navbar/topbar
                nav_match = re.search(r'(<div[^>]*id=["\'](?:topbar|navbar)["\'][^>]*>.*?</div>\s*)', 
                                    body_content, re.DOTALL | re.IGNORECASE)
                if nav_match:
                    insert_pos = nav_match.end()
                    ai_navigator = '''
    <!-- AI Navigator Sidebar -->
    <div id="ai-navigator-container" 
         data-role="User"
         data-user-id=""
         data-user-name="">
    </div>
'''
                    body_content = (body_content[:insert_pos] + 
                                  ai_navigator + 
                                  body_content[insert_pos:])
                else:
                    # Insert at start of body content
                    ai_navigator = '''
    <!-- AI Navigator Sidebar -->
    <div id="ai-navigator-container" 
         data-role="User"
         data-user-id=""
         data-user-name="">
    </div>
'''
                    body_content = ai_navigator + body_content
            
            content = body_start + body_content + body_end
        
        # Write back
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(content)
        
        return True, "AI Navigator added successfully"
    
    except Exception as e:
        return False, f"Error: {str(e)}"

def get_pages_needing_ai_navigator(schema):
    """Get list of pages that need AI Navigator"""
    pages_needing_ai = []
    
    for page_name, page_info in schema['pages'].items():
        has_ai = page_info.get('has_ai_navigator', False)
        page_type = page_info.get('page_type', 'unknown')
        file_name_lower = page_name.lower()
        file_path = page_info.get('file_path', '')
        
        # Skip API/utility files
        if any(x in file_name_lower for x in ['api', 'get_', 'delete_', 'update_', 'create_', 'php/']):
            continue
        
        # List pages should have AI Navigator
        if (page_type == 'list' or 'list' in file_name_lower) and not has_ai:
            pages_needing_ai.append({
                'name': page_name,
                'path': file_path,
                'type': page_type
            })
        
        # 7Ps registry should have AI Navigator
        if '7ps_registry' in file_name_lower and not has_ai:
            pages_needing_ai.append({
                'name': page_name,
                'path': file_path,
                'type': 'registry'
            })
    
    return pages_needing_ai

def main():
    """Main function"""
    print("Loading schema...")
    schema = load_json(SCHEMA_FILE)
    
    print("\n=== Applying Refactoring Improvements ===\n")
    
    # Get pages needing AI Navigator
    pages_needing_ai = get_pages_needing_ai_navigator(schema)
    
    print(f"Found {len(pages_needing_ai)} pages needing AI Navigator")
    print("\nPages to update:")
    for page in pages_needing_ai[:10]:  # Show first 10
        print(f"  - {page['name']} ({page['path']})")
    if len(pages_needing_ai) > 10:
        print(f"  ... and {len(pages_needing_ai) - 10} more")
    
    # Ask for confirmation
    response = input("\nApply AI Navigator to these pages? (y/n): ").strip().lower()
    if response != 'y':
        print("Cancelled.")
        return
    
    # Apply AI Navigator
    print("\nApplying AI Navigator...")
    success_count = 0
    error_count = 0
    
    for page in pages_needing_ai:
        file_path = BASE_DIR / page['path']
        if not file_path.exists():
            # Try just the filename
            file_path = BASE_DIR / page['name']
        
        if file_path.exists():
            success, message = add_ai_navigator_to_page(file_path)
            if success:
                success_count += 1
                print(f"  ✅ {page['name']}")
            else:
                error_count += 1
                print(f"  ⚠️  {page['name']}: {message}")
        else:
            error_count += 1
            print(f"  ❌ {page['name']}: File not found")
    
    print(f"\n=== Results ===")
    print(f"✅ Success: {success_count}")
    print(f"⚠️  Errors/Skipped: {error_count}")
    print(f"\nNext steps:")
    print("1. Re-run: python PY/frontendschema.py")
    print("2. Validate: python PY/validate_refactoring.py")
    print("3. Test the updated pages")

if __name__ == '__main__':
    try:
        main()
    except Exception as e:
        print(f"Error: {e}")
        import traceback
        traceback.print_exc()

