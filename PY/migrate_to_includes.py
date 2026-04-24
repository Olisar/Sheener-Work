# File: sheener/PY/migrate_to_includes.py
#!/usr/bin/env python3
"""
migrate_to_includes.py - Identifies pages that can be migrated to use includes/header.php and includes/footer.php
"""

import json
import re
from pathlib import Path
from collections import defaultdict

BASE_DIR = Path(__file__).parent.parent
SCHEMA_FILE = BASE_DIR / 'py' / 'frontendschema.json'

def load_json(file_path):
    """Load JSON file"""
    with open(file_path, 'r', encoding='utf-8') as f:
        return json.load(f)

def analyze_page_structure(file_path):
    """Analyze if a page can use includes/header.php"""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
        
        # Check if it's PHP (can use includes)
        is_php = file_path.suffix == '.php'
        
        # Check for standard patterns
        has_doctype = '<!DOCTYPE' in content
        has_head = '<head' in content
        has_body = '<body' in content
        has_navbar = 'navbar.js' in content or 'id="navbar"' in content
        has_topbar = 'topbar.js' in content or 'id="topbar"' in content
        has_ai_navigator = 'ai-navigator.js' in content or 'ai-navigator-container' in content
        
        # Check if already using includes
        uses_includes = 'includes/header.php' in content or 'includes/footer.php' in content
        
        # Count script/stylesheet tags
        script_count = len(re.findall(r'<script[^>]*src=', content, re.IGNORECASE))
        stylesheet_count = len(re.findall(r'<link[^>]*rel=["\']stylesheet["\']', content, re.IGNORECASE))
        
        return {
            'is_php': is_php,
            'has_doctype': has_doctype,
            'has_head': has_head,
            'has_body': has_body,
            'has_navbar': has_navbar,
            'has_topbar': has_topbar,
            'has_ai_navigator': has_ai_navigator,
            'uses_includes': uses_includes,
            'script_count': script_count,
            'stylesheet_count': stylesheet_count,
            'can_migrate': is_php and has_doctype and has_head and has_body and not uses_includes
        }
    except Exception as e:
        return {'error': str(e)}

def main():
    """Main function"""
    print("Loading schema...")
    schema = load_json(SCHEMA_FILE)
    
    print("\n=== Analyzing Pages for Migration ===\n")
    
    candidates = []
    already_using = []
    not_php = []
    
    for page_name, page_info in schema['pages'].items():
        file_path = BASE_DIR / page_info.get('file_path', page_name)
        
        if not file_path.exists():
            continue
        
        analysis = analyze_page_structure(file_path)
        
        if analysis.get('error'):
            continue
        
        if analysis.get('uses_includes'):
            already_using.append(page_name)
        elif analysis.get('can_migrate'):
            # Good candidate for migration
            candidates.append({
                'name': page_name,
                'path': str(file_path.relative_to(BASE_DIR)),
                'has_navbar': analysis.get('has_navbar', False),
                'has_topbar': analysis.get('has_topbar', False),
                'has_ai_navigator': analysis.get('has_ai_navigator', False),
                'script_count': analysis.get('script_count', 0),
                'stylesheet_count': analysis.get('stylesheet_count', 0)
            })
        elif not analysis.get('is_php'):
            not_php.append(page_name)
    
    print(f"✅ Pages already using includes: {len(already_using)}")
    print(f"📋 PHP pages that can be migrated: {len(candidates)}")
    print(f"📄 Non-PHP pages (HTML): {len(not_php)}")
    
    # Show top candidates
    if candidates:
        print("\n=== Top Migration Candidates ===")
        print("\nPages with navbar, topbar, and AI Navigator (easiest to migrate):")
        easy_candidates = [c for c in candidates if c['has_navbar'] and c['has_topbar'] and c['has_ai_navigator']]
        for i, candidate in enumerate(easy_candidates[:10], 1):
            print(f"  {i}. {candidate['name']}")
            print(f"     Path: {candidate['path']}")
            print(f"     Scripts: {candidate['script_count']}, Stylesheets: {candidate['stylesheet_count']}")
        
        if len(easy_candidates) > 10:
            print(f"     ... and {len(easy_candidates) - 10} more")
        
        print("\nAll candidates saved to: py/migration_candidates.json")
        
        # Save to JSON
        output_file = BASE_DIR / 'py' / 'migration_candidates.json'
        with open(output_file, 'w', encoding='utf-8') as f:
            json.dump({
                'candidates': candidates,
                'already_using': already_using,
                'not_php': not_php[:50],  # Limit to first 50
                'summary': {
                    'total_candidates': len(candidates),
                    'easy_candidates': len(easy_candidates),
                    'already_using': len(already_using)
                }
            }, f, indent=2)
        
        print(f"\n✅ Analysis complete!")
        print(f"   - {len(candidates)} PHP pages can be migrated")
        print(f"   - {len(easy_candidates)} are easy candidates (have navbar, topbar, AI Navigator)")
    else:
        print("\n⚠️  No migration candidates found")

if __name__ == '__main__':
    main()

