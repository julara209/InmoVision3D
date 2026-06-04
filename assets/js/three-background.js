/**
 * InmoVision 3D - Three.js Background Animation
 */

document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('canvas-container');
    
    if (!container || typeof THREE === 'undefined') return;

    // Scene setup
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    const renderer = new THREE.WebGLRenderer({ 
        antialias: true, 
        alpha: true 
    });
    
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    container.appendChild(renderer.domElement);

    // Create floating houses/buildings
    const buildings = [];
    const buildingGroup = new THREE.Group();

    // Create a simple house shape
    function createHouse(x, y, z, scale, color) {
        const group = new THREE.Group();

        // House body
        const bodyGeometry = new THREE.BoxGeometry(1, 0.8, 1);
        const bodyMaterial = new THREE.MeshBasicMaterial({ 
            color: color,
            transparent: true,
            opacity: 0.3,
            wireframe: true
        });
        const body = new THREE.Mesh(bodyGeometry, bodyMaterial);
        group.add(body);

        // Roof
        const roofGeometry = new THREE.ConeGeometry(0.9, 0.5, 4);
        const roofMaterial = new THREE.MeshBasicMaterial({ 
            color: 0x0ea5e9,
            transparent: true,
            opacity: 0.4,
            wireframe: true
        });
        const roof = new THREE.Mesh(roofGeometry, roofMaterial);
        roof.position.y = 0.65;
        roof.rotation.y = Math.PI / 4;
        group.add(roof);

        group.position.set(x, y, z);
        group.scale.set(scale, scale, scale);
        
        return group;
    }

    // Create multiple floating houses
    const colors = [0x0ea5e9, 0xf97316, 0x22c55e, 0xa855f7, 0xeab308];
    
    for (let i = 0; i < 15; i++) {
        const x = (Math.random() - 0.5) * 20;
        const y = (Math.random() - 0.5) * 10;
        const z = (Math.random() - 0.5) * 15 - 5;
        const scale = Math.random() * 0.5 + 0.3;
        const color = colors[Math.floor(Math.random() * colors.length)];
        
        const house = createHouse(x, y, z, scale, color);
        house.userData = {
            rotationSpeed: (Math.random() - 0.5) * 0.01,
            floatSpeed: Math.random() * 0.02 + 0.01,
            floatOffset: Math.random() * Math.PI * 2
        };
        
        buildings.push(house);
        buildingGroup.add(house);
    }

    scene.add(buildingGroup);

    // Create particle system
    const particlesGeometry = new THREE.BufferGeometry();
    const particlesCount = 500;
    const posArray = new Float32Array(particlesCount * 3);

    for (let i = 0; i < particlesCount * 3; i++) {
        posArray[i] = (Math.random() - 0.5) * 30;
    }

    particlesGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));

    const particlesMaterial = new THREE.PointsMaterial({
        size: 0.02,
        color: 0x0ea5e9,
        transparent: true,
        opacity: 0.6
    });

    const particlesMesh = new THREE.Points(particlesGeometry, particlesMaterial);
    scene.add(particlesMesh);

    // Camera position
    camera.position.z = 8;

    // Mouse interaction
    let mouseX = 0;
    let mouseY = 0;
    let targetX = 0;
    let targetY = 0;

    document.addEventListener('mousemove', (event) => {
        mouseX = (event.clientX / window.innerWidth) * 2 - 1;
        mouseY = -(event.clientY / window.innerHeight) * 2 + 1;
    });

    // Animation loop
    const clock = new THREE.Clock();

    function animate() {
        requestAnimationFrame(animate);

        const elapsedTime = clock.getElapsedTime();

        // Smooth camera movement based on mouse
        targetX = mouseX * 0.5;
        targetY = mouseY * 0.3;

        buildingGroup.rotation.y += (targetX - buildingGroup.rotation.y) * 0.02;
        buildingGroup.rotation.x += (targetY - buildingGroup.rotation.x) * 0.02;

        // Animate individual buildings
        buildings.forEach((building, index) => {
            building.rotation.y += building.userData.rotationSpeed;
            building.position.y += Math.sin(elapsedTime * building.userData.floatSpeed + building.userData.floatOffset) * 0.002;
        });

        // Rotate particles
        particlesMesh.rotation.y = elapsedTime * 0.05;
        particlesMesh.rotation.x = elapsedTime * 0.02;

        renderer.render(scene, camera);
    }

    animate();

    // Handle window resize
    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });
});
