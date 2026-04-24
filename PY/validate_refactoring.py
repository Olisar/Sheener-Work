# File: sheener/PY/validate_refactoring.py
#!/usr/bin/env python3
"""
validate_refactoring.py - Validates refactoring progress against the plan
Run this after each phase to check progress
"""

import json
import sys
from pathlib import Path
from collections import defaultdict

BASE_DIR = Path(__file__).parent.parent
SCHEMA_FILE = BASE_DIR / 'py' / 'frontendschema.json'
PLAN_FILE = BASE_DIR / 'py' / 'refactoring_plan.json'

def load_json(file_path):
    """Load JSON file"""
    with open(file_path, 'r', encoding='utf-8') as f:
        return json.load(f)

def validate_dashboards(schema, plan):
    """Validate dashboard consolidation"""
    print("\n=== Dashboard Consolidation ===")
    
    canonical = plan['current_state']['canonical_dashboards']
    deprecated = plan['refactoring_actions']['step_3_consolidate_dashboards']['deprecated']
    
    # Check if deprecated dashboards are still referenced
    all_deprecated = []
    for pages in deprecated.values():
        all_deprecated.extend(pages)
    
    still_referenced = []
    for page_name in all_deprecated:
        if page_name in schema['relationships']['reverse']:
            still_referenced.append(page_name)
    
    if still_referenced:
        print(f"❌ {len(still_referenced)} deprecated dashboards still referenced:")
        for page in still_referenced:
            print(f"   - {page}")
    else:
        print("✅ No deprecated dashboards are referenced")
    
    # Check canonical dashboards exist
    missing_canonical = []
    for role, dashboard in canonical.items():
        if dashboard and dashboard not in schema['pages']:
            missing_canonical.append(f"{role}: {dashboard}")
    
    if missing_canonical:
        print(f"❌ Missing canonical dashboards:")
        for item in missing_canonical:
            print(f"   - {item}")
    else:
        print("✅ All canonical dashboards exist")

def validate_naming_conventions(schema):
    """Validate naming conventions"""
    print("\n=== Naming Conventions ===")
    
    conventions = {
        'list': lambda n: '_list.php' in n or '_list.html' in n,
        'form': lambda n: '_form.php' in n or '_form.html' in n,
        'view': lambda n: '_view.php' in n or '_view.html' in n
    }
    
    violations = defaultdict(list)
    
    for page_name, page_info in schema['pages'].items():
        page_type = page_info.get('page_type', 'unknown')
        file_name_lower = page_name.lower()
        
        if page_type == 'list' and not conventions['list'](page_name):
            if 'list' in file_name_lower:
                violations['list'].append(page_name)
        
        if page_type == 'form' and not conventions['form'](page_name):
            if 'form' in file_name_lower or 'create' in file_name_lower:
                violations['form'].append(page_name)
    
    if violations:
        print(f"⚠️  Naming convention violations found:")
        for conv_type, pages in violations.items():
            print(f"   {conv_type}: {len(pages)} pages")
            if len(pages) <= 5:
                for page in pages:
                    print(f"      - {page}")
    else:
        print("✅ All pages follow naming conventions")

def validate_ai_navigator(schema, plan):
    """Validate AI Navigator usage"""
    print("\n=== AI Navigator Usage ===")
    
    pages_with_ai = []
    list_pages_without_ai = []
    
    for page_name, page_info in schema['pages'].items():
        has_ai = page_info.get('has_ai_navigator', False)
        page_type = page_info.get('page_type', 'unknown')
        file_name_lower = page_name.lower()
        
        if has_ai:
            pages_with_ai.append(page_name)
        
        if (page_type == 'list' or 'list' in file_name_lower) and not has_ai:
            # Check if it's a primary list page (not API or utility)
            if not any(x in file_name_lower for x in ['api', 'get_', 'delete_', 'update_', 'create_']):
                list_pages_without_ai.append(page_name)
    
    print(f"✅ Pages with AI Navigator: {len(pages_with_ai)}")
    
    if list_pages_without_ai:
        print(f"⚠️  List pages missing AI Navigator ({len(list_pages_without_ai)}):")
        for page in list_pages_without_ai[:10]:  # Show first 10
            print(f"   - {page}")
        if len(list_pages_without_ai) > 10:
            print(f"   ... and {len(list_pages_without_ai) - 10} more")
    else:
        print("✅ All list pages have AI Navigator")

def validate_6ps_structure(schema):
    """Validate 6Ps structure"""
    print("\n=== 6Ps Structure ===")
    
    sixps_pages = ['people_list.php', 'material_list.php', 'area_list.php', 
                   'equipment_list.php', 'energy_list.php', 'sop_list.php']
    
    missing = []
    missing_ai = []
    
    for page in sixps_pages:
        if page not in schema['pages']:
            missing.append(page)
        else:
            if not schema['pages'][page].get('has_ai_navigator', False):
                missing_ai.append(page)
    
    if missing:
        print(f"❌ Missing 6Ps pages: {', '.join(missing)}")
    else:
        print("✅ All 6Ps pages exist")
    
    if missing_ai:
        print(f"⚠️  6Ps pages missing AI Navigator: {', '.join(missing_ai)}")
    else:
        print("✅ All 6Ps pages have AI Navigator")
    
    # Check 7Ps registry
    if '7ps_registry.html' in schema['pages']:
        has_ai = schema['pages']['7ps_registry.html'].get('has_ai_navigator', False)
        if has_ai:
            print("✅ 7Ps registry has AI Navigator")
        else:
            print("⚠️  7Ps registry missing AI Navigator")
    else:
        print("❌ 7Ps registry not found")

def validate_components(schema):
    """Validate component reuse"""
    print("\n=== Component Reuse ===")
    
    # Check for common patterns
    navbar_count = sum(1 for p in schema['pages'].values() if p.get('has_navbar', False))
    topbar_count = sum(1 for p in schema['pages'].values() if p.get('has_topbar', False))
    
    total_pages = len(schema['pages'])
    navbar_pct = (navbar_count / total_pages) * 100
    topbar_pct = (topbar_count / total_pages) * 100
    
    print(f"Navbar usage: {navbar_count}/{total_pages} ({navbar_pct:.1f}%)")
    print(f"Topbar usage: {topbar_count}/{total_pages} ({topbar_pct:.1f}%)")
    
    if navbar_pct < 80:
        print("⚠️  Consider increasing navbar usage")
    if topbar_pct < 80:
        print("⚠️  Consider increasing topbar usage")

def validate_unreferenced_pages(schema):
    """Check for unreferenced pages that should be cleaned up"""
    print("\n=== Unreferenced Pages ===")
    
    all_targets = set()
    for rels in schema['relationships']['forward'].values():
        for rel in rels:
            all_targets.add(rel['target'])
    
    unreferenced = []
    for page_name in schema['pages'].keys():
        if page_name not in all_targets:
            page_info = schema['pages'][page_name]
            if ('test' in page_name.lower() or 
                page_info.get('page_type') == 'unknown' or
                'prototype' in page_name.lower()):
                unreferenced.append(page_name)
    
    if unreferenced:
        print(f"⚠️  {len(unreferenced)} unreferenced test/unknown pages found")
        print("   Consider archiving or removing these pages")
    else:
        print("✅ No unreferenced test/unknown pages")

def main():
    """Main validation function"""
    print("Loading schema and plan...")
    
    try:
        schema = load_json(SCHEMA_FILE)
        plan = load_json(PLAN_FILE)
    except FileNotFoundError as e:
        print(f"❌ Error: {e}")
        print("Please run frontendschema.py and refactoring_plan.py first")
        sys.exit(1)
    
    print(f"\nValidating refactoring progress...")
    print(f"Total pages: {schema['metadata']['total_pages']}")
    
    validate_dashboards(schema, plan)
    validate_naming_conventions(schema)
    validate_ai_navigator(schema, plan)
    validate_6ps_structure(schema)
    validate_components(schema)
    validate_unreferenced_pages(schema)
    
    print("\n=== Validation Complete ===")
    print("\nNext steps:")
    print("1. Address any issues marked with ❌ or ⚠️")
    print("2. Re-run frontendschema.py after making changes")
    print("3. Re-run this validation script to check progress")

if __name__ == '__main__':
    main()

