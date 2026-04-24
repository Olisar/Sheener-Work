# File: sheener/test_node/treePY.py
import os
from vpython import cylinder, sphere, vector, color, box
import random  # Import the random module

# Force VPython to use Chrome
os.environ["BROWSER"] = "C:/Program Files/Google/Chrome/Application/chrome.exe"  # Update path if necessary

# Set a fixed port for VPython
os.environ["VPYTHON_PORT"] = "8080"  # Change to any preferred port if needed

def create_realistic_tree(position, trunk_height=5, trunk_segments=8, branch_count=10, foliage_density=30):
    # Define colors
    brown = vector(0.5, 0.35, 0.2)  # Realistic brown for the trunk
    foliage_colors = [
        vector(0.3, 0.6, 0.3),  # Dark green
        vector(0.4, 0.8, 0.4),  # Bright green
        vector(0.5, 0.7, 0.4)   # Muted green
    ]
    
    # Create the trunk with multiple segments for realistic bends
    trunk_base = position
    trunk = []
    for _ in range(trunk_segments):
        bend = vector(random.uniform(-0.2, 0.2), trunk_height / trunk_segments, random.uniform(-0.2, 0.2))
        trunk_segment = cylinder(pos=trunk_base, axis=bend, radius=0.15, color=brown)
        trunk_base += bend
        trunk.append(trunk_segment)
    
    # Create branches
    for _ in range(branch_count):
        branch_pos = trunk_base - vector(0, random.uniform(2.0, trunk_height * 0.6), 0)
        branch_dir = vector(random.uniform(-1, 1), random.uniform(1, 2), random.uniform(-1, 1)).norm()
        branch_length = random.uniform(1.5, 2.5)
        branch = cylinder(pos=branch_pos, axis=branch_dir * branch_length, radius=0.08, color=brown)
        
        # Add foliage clusters to the branches
        for _ in range(foliage_density):  # Number of foliage spheres per branch
            offset = vector(
                random.uniform(-0.5, 0.5),
                random.uniform(-0.5, 0.5),
                random.uniform(-0.5, 0.5)
            )
            foliage_pos = branch.pos + branch.axis + offset
            sphere(
                pos=foliage_pos,
                radius=random.uniform(0.3, 0.5),
                color=random.choice(foliage_colors)
            )
    
    # Add a dense foliage crown at the top of the trunk
    for _ in range(foliage_density * 3):  # Dense foliage at the top
        offset_x = random.uniform(-1.5, 1.5)
        offset_y = random.uniform(0, 1.5)
        offset_z = random.uniform(-1.5, 1.5)
        sphere(
            pos=trunk_base + vector(offset_x, offset_y, offset_z),
            radius=random.uniform(0.4, 0.8),
            color=random.choice(foliage_colors)
        )

def create_realistic_environment():
    # Create a few realistic trees at random positions
    for _ in range(6):  # Number of trees
        x = random.uniform(-15, 15)
        z = random.uniform(-15, 15)
        create_realistic_tree(
            position=vector(x, 0, z),
            trunk_height=random.uniform(6, 8),
            trunk_segments=random.randint(6, 10),
            branch_count=random.randint(8, 12),
            foliage_density=random.randint(20, 40)
        )
    
    # Add a flat ground (grass-like)
    ground = box(pos=vector(0, -0.1, 0), length=50, height=0.2, width=50, color=vector(0.2, 0.7, 0.2))

# Start the visualization
print("Starting VPython visualization...")
create_realistic_environment()

# Keep the script running to prevent the server from closing
print("Visualization running. Open http://localhost:8080 in your browser if it doesn't open automatically.")
input("Press Enter to exit...")
