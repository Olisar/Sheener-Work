# File: sheener/PY/refactoring_plan.py
#!/usr/bin/env python3
"""
refactoring_plan.py - Analyzes frontend schema and generates a detailed refactoring plan
Implements the 10-step architecture consolidation plan
"""

import json
import os
from pathlib import Path
from collections import defaultdict, Counter
from datetime import datetime

BASE_DIR = Path(__file__).parent.parent
SCHEMA_FILE = BASE_DIR / 'py' / 'frontendschema.json'
OUTPUT_DIR = BASE_DIR / 'py'
PLAN_FILE = OUTPUT_DIR / 'refactoring_plan.json'
REPORT_FILE = OUTPUT_DIR / 'refactoring_report.md'

def load_schema():
    """Load the frontend schema JSON"""
    with open(SCHEMA_FILE, 'r', encoding='utf-8') as f:
        return json.load(f)

def analyze_dashboards(schema):
    """Step 3: Identify and categorize all dashboards"""
    dashboards = {
        'employee': [],
        'manager': [],
        'admin': [],
        'permit': [],
        'system': [],
        'other': []
    }
    
    for page_name, page_info in schema['pages'].items():
        if 'dashboard' in page_name.lower():
            file_path = page_info.get('file_path', '')
            
            if 'employee' in page_name.lower():
                dashboards['employee'].append({
                    'name': page_name,
                    'path': file_path,
                    'has_ai_navigator': page_info.get('has_ai_navigator', False),
                    'has_navbar': page_info.get('has_navbar', False),
                    'has_topbar': page_info.get('has_topbar', False)
                })
            elif 'manager' in page_name.lower():
                dashboards['manager'].append({
                    'name': page_name,
                    'path': file_path,
                    'has_ai_navigator': page_info.get('has_ai_navigator', False),
                    'has_navbar': page_info.get('has_navbar', False),
                    'has_topbar': page_info.get('has_topbar', False)
                })
            elif 'admin' in page_name.lower():
                dashboards['admin'].append({
                    'name': page_name,
                    'path': file_path,
                    'has_ai_navigator': page_info.get('has_ai_navigator', False),
                    'has_navbar': page_info.get('has_navbar', False),
                    'has_topbar': page_info.get('has_topbar', False)
                })
            elif 'permit' in page_name.lower():
                dashboards['permit'].append({
                    'name': page_name,
                    'path': file_path,
                    'has_ai_navigator': page_info.get('has_ai_navigator', False),
                    'has_navbar': page_info.get('has_navbar', False),
                    'has_topbar': page_info.get('has_topbar', False)
                })
            elif page_name == 'dashboard.php' or 'dashboard.php' in file_path:
                dashboards['system'].append({
                    'name': page_name,
                    'path': file_path,
                    'has_ai_navigator': page_info.get('has_ai_navigator', False),
                    'has_navbar': page_info.get('has_navbar', False),
                    'has_topbar': page_info.get('has_topbar', False)
                })
            else:
                dashboards['other'].append({
                    'name': page_name,
                    'path': file_path,
                    'has_ai_navigator': page_info.get('has_ai_navigator', False),
                    'has_navbar': page_info.get('has_navbar', False),
                    'has_topbar': page_info.get('has_topbar', False)
                })
    
    return dashboards

def choose_canonical_dashboards(dashboards):
    """Choose the canonical dashboard for each role"""
    canonical = {
        'employee': None,
        'manager': None,
        'admin': None,
        'permit': None,
        'system': None
    }
    
    # Employee: choose most complete variant
    if dashboards['employee']:
        # Prefer .html files with most features
        best = max(dashboards['employee'], 
                  key=lambda x: (x['has_navbar'], x['has_topbar'], x['has_ai_navigator']))
        canonical['employee'] = best['name']
    
    # Manager: dashboard_manager.php
    if dashboards['manager']:
        manager_php = [d for d in dashboards['manager'] if d['name'].endswith('.php')]
        if manager_php:
            canonical['manager'] = manager_php[0]['name']
        else:
            canonical['manager'] = dashboards['manager'][0]['name']
    
    # Admin: dashboard_admin.php
    if dashboards['admin']:
        admin_php = [d for d in dashboards['admin'] if d['name'].endswith('.php')]
        if admin_php:
            canonical['admin'] = admin_php[0]['name']
        else:
            canonical['admin'] = dashboards['admin'][0]['name']
    
    # Permit: dashboard_permit.php
    if dashboards['permit']:
        permit_php = [d for d in dashboards['permit'] if d['name'].endswith('.php')]
        if permit_php:
            canonical['permit'] = permit_php[0]['name']
        else:
            canonical['permit'] = dashboards['permit'][0]['name']
    
    # System: dashboard.php
    if dashboards['system']:
        canonical['system'] = 'dashboard.php'
    
    return canonical

def categorize_pages_by_type(schema):
    """Step 2: Categorize all pages by type and module"""
    categories = {
        'dashboards': [],
        'lists': [],
        'forms': [],
        'details': [],
        'edits': [],
        'views': [],
        'unknown': [],
        'tests': [],
        'analytics': [],
        'training': []
    }
    
    modules = {
        'Permits': [],
        'Waste': [],
        'Change Control': [],
        'Risk Assessment': [],
        'Training': [],
        'Document Control': [],
        '6Ps': [],
        'Other': []
    }
    
    for page_name, page_info in schema['pages'].items():
        page_type = page_info.get('page_type', 'unknown')
        file_path = page_info.get('file_path', '')
        file_name_lower = page_name.lower()
        
        # Categorize by type
        if 'dashboard' in file_name_lower:
            categories['dashboards'].append(page_name)
        elif page_type == 'list' or 'list' in file_name_lower:
            categories['lists'].append(page_name)
        elif page_type == 'form' or 'form' in file_name_lower or 'create' in file_name_lower:
            categories['forms'].append(page_name)
        elif page_type == 'detail' or 'detail' in file_name_lower:
            categories['details'].append(page_name)
        elif page_type == 'edit' or 'edit' in file_name_lower:
            categories['edits'].append(page_name)
        elif page_type == 'view' or 'view' in file_name_lower:
            categories['views'].append(page_name)
        elif 'test' in file_name_lower or 'Test' in page_name:
            categories['tests'].append(page_name)
        elif 'analytics' in file_name_lower or 'kpi' in file_name_lower:
            categories['analytics'].append(page_name)
        elif 'training' in file_name_lower or 'induction' in file_name_lower:
            categories['training'].append(page_name)
        else:
            categories['unknown'].append(page_name)
        
        # Categorize by module
        if 'permit' in file_name_lower or 'ptw' in file_name_lower:
            modules['Permits'].append(page_name)
        elif 'waste' in file_name_lower:
            modules['Waste'].append(page_name)
        elif 'change' in file_name_lower or 'cc_' in file_name_lower or 'changecontrol' in file_name_lower:
            modules['Change Control'].append(page_name)
        elif 'risk' in file_name_lower or 'assessment' in file_name_lower or 'hira' in file_name_lower:
            modules['Risk Assessment'].append(page_name)
        elif 'training' in file_name_lower or 'induction' in file_name_lower:
            modules['Training'].append(page_name)
        elif 'sop' in file_name_lower or 'document' in file_name_lower or 'doccontrol' in file_name_lower:
            modules['Document Control'].append(page_name)
        elif page_info.get('category_6ps') or any(p in file_name_lower for p in ['people', 'material', 'area', 'equipment', 'energy', 'sop']):
            modules['6Ps'].append(page_name)
        else:
            modules['Other'].append(page_name)
    
    return categories, modules

def analyze_entity_patterns(schema):
    """Step 4: Analyze entity patterns (list/form/edit)"""
    entities = defaultdict(lambda: {
        'list': [],
        'form': [],
        'create': [],
        'edit': [],
        'view': []
    })
    
    for page_name, page_info in schema['pages'].items():
        file_name_lower = page_name.lower()
        
        # Extract entity name from common patterns
        entity = None
        if '_list.php' in page_name or '_list.html' in page_name:
            entity = page_name.replace('_list.php', '').replace('_list.html', '')
            entities[entity]['list'].append(page_name)
        elif '_form.php' in page_name or '_form.html' in page_name:
            entity = page_name.replace('_form.php', '').replace('_form.html', '')
            entities[entity]['form'].append(page_name)
        elif 'create_' in file_name_lower:
            entity = page_name.replace('create_', '').replace('.php', '').replace('.html', '')
            entities[entity]['create'].append(page_name)
        elif '_edit.php' in page_name or '_edit.html' in page_name:
            entity = page_name.replace('_edit.php', '').replace('_edit.html', '')
            entities[entity]['edit'].append(page_name)
        elif '_view.php' in page_name or '_view.html' in page_name:
            entity = page_name.replace('_view.php', '').replace('_view.html', '')
            entities[entity]['view'].append(page_name)
    
    return dict(entities)

def find_unreferenced_pages(schema):
    """Step 6: Find pages with no incoming links (candidates for removal)"""
    all_targets = set()
    for rels in schema['relationships']['forward'].values():
        for rel in rels:
            all_targets.add(rel['target'])
    
    unreferenced = []
    for page_name in schema['pages'].keys():
        if page_name not in all_targets:
            page_info = schema['pages'][page_name]
            # Check if it's a test or unknown type
            if ('test' in page_name.lower() or 
                page_info.get('page_type') == 'unknown' or
                'prototype' in page_name.lower() or
                'demo' in page_name.lower()):
                unreferenced.append({
                    'name': page_name,
                    'path': page_info.get('file_path', ''),
                    'type': page_info.get('page_type', 'unknown'),
                    'reason': 'No incoming links and test/unknown type'
                })
    
    return unreferenced

def analyze_components(schema):
    """Step 7: Analyze component reuse"""
    class_counter = Counter()
    id_counter = Counter()
    
    for page_name, page_info in schema['pages'].items():
        for cls in page_info.get('unique_classes', []):
            class_counter[cls] += 1
        for id_val in page_info.get('unique_ids', []):
            id_counter[id_val] += 1
    
    # Identify common components
    common_classes = {
        'table': [cls for cls, count in class_counter.items() if 'table' in cls.lower() and count > 5],
        'modal': [cls for cls, count in class_counter.items() if 'modal' in cls.lower() and count > 3],
        'header': [cls for cls, count in class_counter.items() if 'header' in cls.lower() and count > 5],
        'card': [cls for cls, count in class_counter.items() if 'card' in cls.lower() and count > 5],
        'button': [cls for cls, count in class_counter.items() if 'btn' in cls.lower() and count > 10],
    }
    
    common_ids = {
        'navbar': [id_val for id_val, count in id_counter.items() if 'nav' in id_val.lower() and count > 10],
        'topbar': [id_val for id_val, count in id_counter.items() if 'top' in id_val.lower() and count > 10],
        'ai_navigator': [id_val for id_val, count in id_counter.items() if 'ai' in id_val.lower() and count > 5],
    }
    
    return common_classes, common_ids

def analyze_6ps_structure(schema):
    """Step 5: Analyze 6Ps/7Ps structure"""
    sixps = schema.get('6ps_structure', {})
    
    # Check which 6Ps pages have AI Navigator
    sixps_with_ai = {}
    for category, pages in sixps.items():
        sixps_with_ai[category] = [
            p['file_name'] for p in pages 
            if p.get('has_ai_navigator', False)
        ]
    
    return sixps_with_ai

def analyze_ai_navigator_usage(schema):
    """Step 8: Analyze AI Navigator usage"""
    pages_with_ai = []
    pages_without_ai = []
    
    for page_name, page_info in schema['pages'].items():
        if page_info.get('has_ai_navigator', False):
            pages_with_ai.append({
                'name': page_name,
                'type': page_info.get('page_type', 'unknown'),
                'path': page_info.get('file_path', '')
            })
        else:
            page_type = page_info.get('page_type', 'unknown')
            if page_type in ['list', 'dashboard']:
                pages_without_ai.append({
                    'name': page_name,
                    'type': page_type,
                    'path': page_info.get('file_path', '')
                })
    
    return pages_with_ai, pages_without_ai

def generate_refactoring_plan():
    """Main function to generate comprehensive refactoring plan"""
    print("Loading frontend schema...")
    schema = load_schema()
    
    print("Analyzing architecture...")
    
    # Step 1: Target architecture (defined in plan)
    target_architecture = {
        'dashboards': {
            'employee': 'dashboard_employee.html (most complete variant)',
            'manager': 'dashboard_manager.php',
            'system': 'dashboard.php',
            'permit': 'dashboard_permit.php'
        },
        'conventions': {
            'naming': {
                'list': '{entity}_list.php',
                'form': '{entity}_form.php',
                'view': '{entity}_view.php'
            },
            'components': {
                'navbar': 'Always use navbar.js',
                'topbar': 'Always use topbar.js',
                'ai_navigator': 'All major lists use AI Navigator'
            }
        }
    }
    
    # Step 2 & 3: Categorize pages and analyze dashboards
    categories, modules = categorize_pages_by_type(schema)
    dashboards = analyze_dashboards(schema)
    canonical_dashboards = choose_canonical_dashboards(dashboards)
    
    # Step 4: Analyze entity patterns
    entity_patterns = analyze_entity_patterns(schema)
    
    # Step 5: Analyze 6Ps structure
    sixps_ai_usage = analyze_6ps_structure(schema)
    
    # Step 6: Find unreferenced pages
    unreferenced = find_unreferenced_pages(schema)
    
    # Step 7: Analyze components
    common_classes, common_ids = analyze_components(schema)
    
    # Step 8: Analyze AI Navigator
    pages_with_ai, pages_without_ai = analyze_ai_navigator_usage(schema)
    
    # Step 9: Navigation structure
    nav_structure = schema.get('navigation_structure', {})
    
    # Build comprehensive plan
    plan = {
        'metadata': {
            'generated_date': datetime.now().isoformat(),
            'total_pages': schema['metadata']['total_pages'],
            'schema_file': str(SCHEMA_FILE)
        },
        'target_architecture': target_architecture,
        'current_state': {
            'dashboards': dashboards,
            'canonical_dashboards': canonical_dashboards,
            'categories': categories,
            'modules': modules,
            'entity_patterns': entity_patterns,
            'sixps_structure': sixps_ai_usage,
            'navigation_roots': list(nav_structure.keys())
        },
        'refactoring_actions': {
            'step_3_consolidate_dashboards': {
                'canonical': canonical_dashboards,
                'deprecated': {
                    'employee': [d['name'] for d in dashboards['employee'] if d['name'] != canonical_dashboards.get('employee')],
                    'manager': [d['name'] for d in dashboards['manager'] if d['name'] != canonical_dashboards.get('manager')],
                    'admin': [d['name'] for d in dashboards['admin'] if d['name'] != canonical_dashboards.get('admin')],
                    'permit': [d['name'] for d in dashboards['permit'] if d['name'] != canonical_dashboards.get('permit')],
                    'system': [d['name'] for d in dashboards['system'] if d['name'] != canonical_dashboards.get('system')],
                    'other': [d['name'] for d in dashboards['other']]
                },
                'actions': [
                    'Update navigation to route to canonical dashboards only',
                    'Mark deprecated dashboards as deprecated',
                    'Remove menu links to deprecated dashboards',
                    'Keep deprecated dashboards accessible via direct URL for rollback'
                ]
            },
            'step_4_standardize_entities': {
                'entities_needing_consolidation': {
                    entity: patterns 
                    for entity, patterns in entity_patterns.items() 
                    if len(patterns['form']) > 1 or len(patterns['create']) > 0 or len(patterns['edit']) > 1
                },
                'recommended_patterns': {
                    entity: {
                        'list': patterns['list'][0] if patterns['list'] else None,
                        'form': patterns['form'][0] if patterns['form'] else (patterns['create'][0] if patterns['create'] else None),
                        'edit': patterns['edit'][0] if patterns['edit'] else None,
                        'view': patterns['view'][0] if patterns['view'] else None
                    }
                    for entity, patterns in entity_patterns.items()
                }
            },
            'step_5_6ps_streamline': {
                'current_6ps_pages': schema.get('6ps_structure', {}),
                'recommendations': [
                    'Make 7ps_registry.html the central landing page',
                    'Standardize navigation buttons across all 6Ps list pages',
                    'Replace cross-links with 6Ps switcher control',
                    'Ensure all 6Ps lists have AI Navigator'
                ]
            },
            'step_6_clean_legacy': {
                'unreferenced_pages': unreferenced,
                'test_pages': categories['tests'],
                'unknown_pages': categories['unknown'],
                'actions': [
                    'Archive unreferenced test/legacy pages',
                    'Remove from production if not needed',
                    'Hide from navigation if needed for internal demo'
                ]
            },
            'step_7_component_reuse': {
                'common_classes': common_classes,
                'common_ids': common_ids,
                'recommendations': [
                    'Extract header, navbar, topbar into PHP includes',
                    'Create reusable modal snippets',
                    'Centralize script/stylesheet loading through layout',
                    'Create shared table card component'
                ]
            },
            'step_8_ai_navigator': {
                'current_usage': {
                    'with_ai': len(pages_with_ai),
                    'without_ai_but_should_have': len(pages_without_ai)
                },
                'pages_needing_ai': pages_without_ai,
                'recommendations': [
                    'Add AI Navigator to all primary list pages',
                    'Remove from complex forms with validation',
                    'Standardize AI Navigator selectors for shared layouts'
                ]
            },
            'step_9_navigation_rationalization': {
                'current_roots': list(nav_structure.keys()),
                'recommended_roots': [
                    'index.php',
                    'dashboard.php',
                    '7ps_registry.html',
                    'dashboard_permit.php'
                ],
                'actions': [
                    'Remove/redirect secondary roots',
                    'Ensure all flows reachable from main dashboards',
                    'Re-run schema script to validate hierarchy'
                ]
            }
        },
        'execution_phases': {
            'phase_1_permits_waste': {
                'modules': ['Permits', 'Waste'],
                'pages': modules['Permits'] + modules['Waste'],
                'tasks': [
                    'Consolidate permit dashboards',
                    'Standardize permit list/form/edit',
                    'Standardize waste collection list/form/edit',
                    'Update navigation links'
                ]
            },
            'phase_2_6ps_registry': {
                'modules': ['6Ps'],
                'pages': modules['6Ps'],
                'tasks': [
                    'Make 7ps_registry.html central hub',
                    'Standardize all 6Ps list pages',
                    'Add AI Navigator to all 6Ps lists',
                    'Replace cross-links with switcher'
                ]
            },
            'phase_3_risk_change_control': {
                'modules': ['Risk Assessment', 'Change Control'],
                'pages': modules['Risk Assessment'] + modules['Change Control'],
                'tasks': [
                    'Standardize risk assessment pages',
                    'Standardize change control pages',
                    'Update relationships'
                ]
            },
            'phase_4_training_document': {
                'modules': ['Training', 'Document Control'],
                'pages': modules['Training'] + modules['Document Control'],
                'tasks': [
                    'Standardize training pages',
                    'Standardize document control pages',
                    'Final cleanup'
                ]
            }
        }
    }
    
    # Save plan to JSON
    OUTPUT_DIR.mkdir(exist_ok=True)
    with open(PLAN_FILE, 'w', encoding='utf-8') as f:
        json.dump(plan, f, indent=2, ensure_ascii=False)
    
    # Generate markdown report
    generate_markdown_report(plan)
    
    print(f"\nRefactoring plan generated:")
    print(f"  JSON: {PLAN_FILE}")
    print(f"  Report: {REPORT_FILE}")
    print(f"\nSummary:")
    print(f"  Total pages: {schema['metadata']['total_pages']}")
    print(f"  Dashboards found: {sum(len(v) for v in dashboards.values())}")
    print(f"  Canonical dashboards: {len([v for v in canonical_dashboards.values() if v])}")
    print(f"  Unreferenced pages: {len(unreferenced)}")
    print(f"  Pages with AI Navigator: {len(pages_with_ai)}")
    print(f"  Pages needing AI Navigator: {len(pages_without_ai)}")
    
    return plan

def generate_markdown_report(plan):
    """Generate a human-readable markdown report"""
    with open(REPORT_FILE, 'w', encoding='utf-8') as f:
        f.write("# Frontend Architecture Refactoring Plan\n\n")
        f.write(f"Generated: {plan['metadata']['generated_date']}\n\n")
        
        f.write("## Executive Summary\n\n")
        f.write(f"- Total pages analyzed: {plan['metadata']['total_pages']}\n")
        f.write(f"- Dashboards to consolidate: {sum(len(v) for v in plan['current_state']['dashboards'].values())}\n")
        f.write(f"- Unreferenced pages: {len(plan['refactoring_actions']['step_6_clean_legacy']['unreferenced_pages'])}\n\n")
        
        f.write("## Target Architecture\n\n")
        f.write("### Dashboards\n")
        for role, dashboard in plan['target_architecture']['dashboards'].items():
            f.write(f"- **{role.title()}**: {dashboard}\n")
        
        f.write("\n### Naming Conventions\n")
        for pattern_type, pattern in plan['target_architecture']['conventions']['naming'].items():
            f.write(f"- **{pattern_type}**: `{pattern}`\n")
        
        f.write("\n## Current State Analysis\n\n")
        
        f.write("### Dashboards\n")
        f.write("#### Canonical (Keep)\n")
        for role, dashboard in plan['current_state']['canonical_dashboards'].items():
            if dashboard:
                f.write(f"- **{role.title()}**: `{dashboard}`\n")
        
        f.write("\n#### Deprecated (Remove/Archive)\n")
        deprecated = plan['refactoring_actions']['step_3_consolidate_dashboards']['deprecated']
        for role, pages in deprecated.items():
            if pages:
                f.write(f"- **{role.title()}**: {', '.join(f'`{p}`' for p in pages)}\n")
        
        f.write("\n### Page Categories\n")
        for category, pages in plan['current_state']['categories'].items():
            if pages:
                f.write(f"- **{category.title()}**: {len(pages)} pages\n")
        
        f.write("\n### Modules\n")
        for module, pages in plan['current_state']['modules'].items():
            if pages:
                f.write(f"- **{module}**: {len(pages)} pages\n")
        
        f.write("\n## Refactoring Actions\n\n")
        
        for step_name, step_data in plan['refactoring_actions'].items():
            step_num = step_name.split('_')[0] + ' ' + step_name.split('_')[1] if '_' in step_name else step_name
            f.write(f"### {step_num.replace('_', ' ').title()}\n\n")
            
            if 'actions' in step_data:
                for action in step_data['actions']:
                    f.write(f"- {action}\n")
                f.write("\n")
            
            if 'recommendations' in step_data:
                for rec in step_data['recommendations']:
                    f.write(f"- {rec}\n")
                f.write("\n")
        
        f.write("\n## Execution Phases\n\n")
        for phase_name, phase_data in plan['execution_phases'].items():
            f.write(f"### {phase_name.replace('_', ' ').title()}\n\n")
            f.write(f"**Modules**: {', '.join(phase_data['modules'])}\n\n")
            f.write(f"**Pages**: {len(phase_data['pages'])} pages\n\n")
            f.write("**Tasks**:\n")
            for task in phase_data['tasks']:
                f.write(f"- {task}\n")
            f.write("\n")
        
        f.write("\n## Next Steps\n\n")
        f.write("1. Review the refactoring plan JSON for detailed data\n")
        f.write("2. Start with Phase 1 (Permits & Waste)\n")
        f.write("3. Update navigation links to point to canonical pages\n")
        f.write("4. Test each phase before moving to the next\n")
        f.write("5. Re-run frontendschema.py after each phase to validate changes\n")

if __name__ == '__main__':
    try:
        plan = generate_refactoring_plan()
        print("\n✅ Refactoring plan generation complete!")
    except Exception as e:
        print(f"❌ Error: {e}")
        import traceback
        traceback.print_exc()

