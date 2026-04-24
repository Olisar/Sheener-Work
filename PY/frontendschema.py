# File: sheener/PY/frontendschema.py
#!/usr/bin/env python3
"""
frontendschema.py - Analyzes and extracts the frontend schema in detail
Creates py/frontendschema.json with page structure, relationships, and navigation hierarchy
"""

import os
import re
import json
from pathlib import Path
from collections import defaultdict
from html.parser import HTMLParser
from urllib.parse import urlparse, urljoin

# Configuration
BASE_DIR = Path(__file__).parent.parent
OUTPUT_DIR = BASE_DIR / 'py'
OUTPUT_FILE = OUTPUT_DIR / 'frontendschema.json'

# File extensions to analyze
FRONTEND_EXTENSIONS = {'.html', '.php', '.htm'}

# Directories to exclude
EXCLUDE_DIRS = {
    'node_modules', 'vendor', '.git', 'tcpdf', 'uploads', 
    'blank', 'docs', 'PNG', 'test_node', 'RiskAssessmentTest',
    'Processflow', 'Presentation', 'HSTopics', 'database_migrations',
    'data', 'sql', 'lib', 'sheener'
}

class FrontendPageParser(HTMLParser):
    """Parser to extract page information from HTML/PHP files"""
    
    def __init__(self, file_path):
        super().__init__()
        self.file_path = file_path
        self.title = None
        self.links = []
        self.scripts = []
        self.stylesheets = []
        self.forms = []
        self.buttons = []
        self.modals = []
        self.ids = []
        self.classes = []
        self.meta_info = {}
        self.in_title = False
        self.current_tag = None
        self.current_attrs = {}
        
    def handle_starttag(self, tag, attrs):
        self.current_tag = tag
        self.current_attrs = dict(attrs)
        
        # Extract title
        if tag == 'title':
            self.in_title = True
            
        # Extract links
        if tag == 'a' and 'href' in self.current_attrs:
            href = self.current_attrs['href']
            if href and not href.startswith(('javascript:', 'mailto:', 'tel:', '#')):
                self.links.append({
                    'href': href,
                    'text': '',
                    'target': self.current_attrs.get('target', ''),
                    'class': self.current_attrs.get('class', '')
                })
        
        # Extract scripts
        if tag == 'script' and 'src' in self.current_attrs:
            self.scripts.append(self.current_attrs['src'])
            
        # Extract stylesheets
        if tag == 'link' and self.current_attrs.get('rel') == 'stylesheet':
            if 'href' in self.current_attrs:
                self.stylesheets.append(self.current_attrs['href'])
        
        # Extract forms
        if tag == 'form':
            form_info = {
                'action': self.current_attrs.get('action', ''),
                'method': self.current_attrs.get('method', 'GET'),
                'id': self.current_attrs.get('id', ''),
                'name': self.current_attrs.get('name', '')
            }
            self.forms.append(form_info)
        
        # Extract buttons with onclick navigation
        if tag == 'button':
            onclick = self.current_attrs.get('onclick', '')
            if 'location.href' in onclick or 'navigateTo' in onclick:
                # Extract the target page from onclick
                href_match = re.search(r"['\"]([^'\"]+\.(?:php|html|htm))['\"]", onclick)
                if href_match:
                    self.buttons.append({
                        'type': 'navigation',
                        'target': href_match.group(1),
                        'onclick': onclick,
                        'text': '',
                        'id': self.current_attrs.get('id', ''),
                        'class': self.current_attrs.get('class', '')
                    })
        
        # Extract modals
        if 'id' in self.current_attrs:
            modal_id = self.current_attrs['id']
            if 'modal' in modal_id.lower():
                self.modals.append({
                    'id': modal_id,
                    'class': self.current_attrs.get('class', '')
                })
        
        # Collect IDs and classes
        if 'id' in self.current_attrs:
            self.ids.append(self.current_attrs['id'])
        if 'class' in self.current_attrs:
            classes = self.current_attrs['class'].split()
            self.classes.extend(classes)
    
    def handle_data(self, data):
        if self.in_title:
            if self.title:
                self.title += data.strip()
            else:
                self.title = data.strip()
        
        # Capture link text
        if self.current_tag == 'a' and data.strip():
            if self.links:
                self.links[-1]['text'] += data.strip()
        
        # Capture button text
        if self.current_tag == 'button' and data.strip():
            if self.buttons:
                self.buttons[-1]['text'] += data.strip()
    
    def handle_endtag(self, tag):
        if tag == 'title':
            self.in_title = False
        self.current_tag = None
        self.current_attrs = {}

def normalize_path(path, base_dir):
    """Normalize file path relative to base directory"""
    try:
        rel_path = os.path.relpath(path, base_dir)
        return rel_path.replace('\\', '/')
    except:
        return str(path)

def extract_page_info(file_path):
    """Extract detailed information from a frontend file"""
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
        
        parser = FrontendPageParser(file_path)
        parser.feed(content)
        
        # Extract additional metadata
        meta_info = {}
        
        # Check for AI Navigator
        has_ai_navigator = 'ai-navigator' in content.lower()
        
        # Check for navbar/topbar
        has_navbar = 'navbar' in content.lower() or 'nav-bar' in content.lower()
        has_topbar = 'topbar' in content.lower() or 'top-bar' in content.lower()
        
        # Extract page type hints
        page_type = 'unknown'
        if 'dashboard' in file_path.lower():
            page_type = 'dashboard'
        elif 'list' in file_path.lower():
            page_type = 'list'
        elif 'form' in file_path.lower() or 'create' in file_path.lower():
            page_type = 'form'
        elif 'detail' in file_path.lower() or 'view' in file_path.lower():
            page_type = 'detail'
        elif 'edit' in file_path.lower():
            page_type = 'edit'
        
        # Extract 6Ps category if applicable
        category_6ps = None
        if 'people' in file_path.lower():
            category_6ps = 'People'
        elif 'material' in file_path.lower() or 'product' in file_path.lower():
            category_6ps = 'Products'
        elif 'area' in file_path.lower() or 'place' in file_path.lower():
            category_6ps = 'Places'
        elif 'equipment' in file_path.lower() or 'plant' in file_path.lower():
            category_6ps = 'Plants'
        elif 'sop' in file_path.lower() or 'process' in file_path.lower():
            category_6ps = 'Processes'
        elif 'energy' in file_path.lower() or 'power' in file_path.lower():
            category_6ps = 'Power'
        
        return {
            'file_path': normalize_path(file_path, BASE_DIR),
            'file_name': os.path.basename(file_path),
            'title': parser.title or os.path.basename(file_path),
            'page_type': page_type,
            'category_6ps': category_6ps,
            'has_ai_navigator': has_ai_navigator,
            'has_navbar': has_navbar,
            'has_topbar': has_topbar,
            'links': parser.links,
            'scripts': parser.scripts,
            'stylesheets': parser.stylesheets,
            'forms': parser.forms,
            'navigation_buttons': parser.buttons,
            'modals': parser.modals,
            'unique_ids': list(set(parser.ids)),
            'unique_classes': list(set(parser.classes)),
            'meta_info': meta_info
        }
    except Exception as e:
        print(f"Error processing {file_path}: {e}")
        return None

def build_relationship_graph(pages):
    """Build parent-child relationship graph from pages"""
    relationships = defaultdict(list)
    reverse_relationships = defaultdict(list)
    
    # Map file names to full page info
    file_map = {page['file_name']: page for page in pages}
    path_map = {page['file_path']: page for page in pages}
    
    for page in pages:
        page_name = page['file_name']
        page_path = page['file_path']
        
        # Find links to other pages
        linked_pages = set()
        
        # From direct links
        for link in page['links']:
            href = link['href']
            # Resolve relative paths
            if not href.startswith(('http://', 'https://', '//')):
                # Try to find the target page
                target_name = os.path.basename(href.split('?')[0].split('#')[0])
                if target_name in file_map:
                    linked_pages.add(target_name)
        
        # From navigation buttons
        for button in page['navigation_buttons']:
            target = button.get('target', '')
            if target:
                target_name = os.path.basename(target.split('?')[0].split('#')[0])
                if target_name in file_map:
                    linked_pages.add(target_name)
        
        # Build relationships
        for linked_page in linked_pages:
            if linked_page != page_name:  # Avoid self-references
                relationships[page_name].append({
                    'target': linked_page,
                    'type': 'navigation',
                    'relationship': 'child'
                })
                reverse_relationships[linked_page].append({
                    'source': page_name,
                    'type': 'navigation',
                    'relationship': 'parent'
                })
    
    return relationships, reverse_relationships

def categorize_pages(pages):
    """Categorize pages into logical groups"""
    categories = {
        '6Ps': [],
        'Dashboards': [],
        'Forms': [],
        'Lists': [],
        'Details': [],
        'Analytics': [],
        'Training': [],
        'Risk Assessment': [],
        'Other': []
    }
    
    for page in pages:
        categorized = False
        
        # 6Ps pages
        if page.get('category_6ps'):
            categories['6Ps'].append(page['file_name'])
            categorized = True
        
        # Dashboards
        if 'dashboard' in page['file_path'].lower():
            categories['Dashboards'].append(page['file_name'])
            categorized = True
        
        # Forms
        if page['page_type'] == 'form' or 'form' in page['file_path'].lower():
            categories['Forms'].append(page['file_name'])
            categorized = True
        
        # Lists
        if page['page_type'] == 'list' or 'list' in page['file_path'].lower():
            categories['Lists'].append(page['file_name'])
            categorized = True
        
        # Details/Views
        if page['page_type'] in ['detail', 'view']:
            categories['Details'].append(page['file_name'])
            categorized = True
        
        # Analytics
        if 'analytics' in page['file_path'].lower() or 'kpi' in page['file_path'].lower():
            categories['Analytics'].append(page['file_name'])
            categorized = True
        
        # Training
        if 'training' in page['file_path'].lower() or 'induction' in page['file_path'].lower():
            categories['Training'].append(page['file_name'])
            categorized = True
        
        # Risk Assessment
        if 'risk' in page['file_path'].lower() or 'assessment' in page['file_path'].lower():
            categories['Risk Assessment'].append(page['file_name'])
            categorized = True
        
        if not categorized:
            categories['Other'].append(page['file_name'])
    
    return categories

def analyze_frontend_schema():
    """Main function to analyze frontend schema"""
    print("Analyzing frontend schema...")
    
    # Create output directory
    OUTPUT_DIR.mkdir(exist_ok=True)
    
    # Find all frontend files
    frontend_files = []
    for root, dirs, files in os.walk(BASE_DIR):
        # Filter out excluded directories
        dirs[:] = [d for d in dirs if d not in EXCLUDE_DIRS]
        
        for file in files:
            if any(file.endswith(ext) for ext in FRONTEND_EXTENSIONS):
                file_path = os.path.join(root, file)
                frontend_files.append(file_path)
    
    print(f"Found {len(frontend_files)} frontend files")
    
    # Extract information from each file
    pages = []
    for file_path in frontend_files:
        page_info = extract_page_info(file_path)
        if page_info:
            pages.append(page_info)
    
    print(f"Processed {len(pages)} pages")
    
    # Build relationship graph
    relationships, reverse_relationships = build_relationship_graph(pages)
    
    # Categorize pages
    categories = categorize_pages(pages)
    
    # Build comprehensive schema
    schema = {
        'metadata': {
            'total_pages': len(pages),
            'analysis_date': str(Path(__file__).stat().st_mtime),
            'base_directory': str(BASE_DIR)
        },
        'pages': {page['file_name']: page for page in pages},
        'relationships': {
            'forward': {k: v for k, v in relationships.items()},
            'reverse': {k: v for k, v in reverse_relationships.items()}
        },
        'categories': categories,
        'navigation_structure': build_navigation_tree(pages, relationships),
        '6ps_structure': extract_6ps_structure(pages, relationships)
    }
    
    # Write to JSON file
    with open(OUTPUT_FILE, 'w', encoding='utf-8') as f:
        json.dump(schema, f, indent=2, ensure_ascii=False)
    
    print(f"Frontend schema exported to '{OUTPUT_FILE}'")
    print(f"Total pages: {len(pages)}")
    print(f"Total relationships: {sum(len(v) for v in relationships.values())}")
    print(f"6Ps pages: {len(categories['6Ps'])}")
    
    return schema

def build_navigation_tree(pages, relationships):
    """Build a hierarchical navigation tree"""
    tree = {}
    
    # Find root pages (pages with no incoming links or entry points)
    root_pages = []
    all_targets = set()
    for rels in relationships.values():
        for rel in rels:
            all_targets.add(rel['target'])
    
    for page in pages:
        if page['file_name'] not in all_targets or page['file_name'] in ['index.php', 'index.html', 'dashboard.php']:
            root_pages.append(page['file_name'])
    
    def build_node(page_name, visited=None):
        if visited is None:
            visited = set()
        
        if page_name in visited:
            return None
        
        visited.add(page_name)
        
        node = {
            'page': page_name,
            'children': []
        }
        
        if page_name in relationships:
            for rel in relationships[page_name]:
                child_node = build_node(rel['target'], visited.copy())
                if child_node:
                    node['children'].append(child_node)
        
        return node
    
    for root in root_pages[:10]:  # Limit to top 10 roots
        tree[root] = build_node(root)
    
    return tree

def extract_6ps_structure(pages, relationships):
    """Extract the 6Ps structure and relationships"""
    sixps_pages = [p for p in pages if p.get('category_6ps')]
    
    structure = {
        'People': [],
        'Products': [],
        'Places': [],
        'Plants': [],
        'Processes': [],
        'Power': []
    }
    
    for page in sixps_pages:
        category = page['category_6ps']
        if category in structure:
            page_info = {
                'file_name': page['file_name'],
                'file_path': page['file_path'],
                'title': page['title'],
                'has_ai_navigator': page['has_ai_navigator'],
                'links_to': []
            }
            
            # Find what this page links to
            if page['file_name'] in relationships:
                for rel in relationships[page['file_name']]:
                    page_info['links_to'].append(rel['target'])
            
            structure[category].append(page_info)
    
    return structure

if __name__ == '__main__':
    try:
        schema = analyze_frontend_schema()
        print("\nAnalysis complete!")
        print(f"Schema saved to: {OUTPUT_FILE}")
    except Exception as e:
        print(f"Error: {e}")
        import traceback
        traceback.print_exc()

