window.confetti = function() {
    const duration = 2000;
    const end = Date.now() + duration;

    const colors = ['#6366f1', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444'];
    const amount = 50;
    
    for(let i=0; i<amount; i++) {
        createParticle();
    }

    function createParticle() {
        const particle = document.createElement('div');
        particle.style.position = 'fixed';
        particle.style.top = '50%';
        particle.style.left = '50%';
        particle.style.width = '10px';
        particle.style.height = '10px';
        particle.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
        particle.style.borderRadius = '50%';
        particle.style.pointerEvents = 'none';
        particle.style.zIndex = '9999';
        document.body.appendChild(particle);

        const angle = Math.random() * Math.PI * 2;
        const velocity = 5 + Math.random() * 15;
        const vx = Math.cos(angle) * velocity;
        const vy = Math.sin(angle) * velocity - 5;
        
        let x = 0, y = 0, opacity = 1;
        
        function animate() {
            x += vx;
            y += vy + 2; // gravity 
            opacity -= 0.02;
            
            particle.style.transform = `translate(-50%, -50%) translate(${x}px, ${y}px)`;
            particle.style.opacity = opacity;
            
            if(Date.now() < end && opacity > 0) {
                requestAnimationFrame(animate);
            } else {
                particle.remove();
            }
        }
        requestAnimationFrame(animate);
    }
};
