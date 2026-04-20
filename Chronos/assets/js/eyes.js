document.addEventListener('mousemove', (e) => {
    const eyes = document.querySelectorAll('.eye');
    
    eyes.forEach(eye => {
        const rect = eye.getBoundingClientRect();
        
        // Eye center
        const eyeX = rect.left + rect.width / 2;
        const eyeY = rect.top + rect.height / 2;
        
        // Delta between pointer and eye center
        const deltaX = e.clientX - eyeX;
        const deltaY = e.clientY - eyeY;
        
        // Calculate rotation angle
        const angle = Math.atan2(deltaY, deltaX);
        
        // Maximum distance pupil can move from center
        const maxDist = (rect.width / 2) - 10;
        
        // Dampen distance based on cursor proximity naturally
        const distToCursor = Math.hypot(deltaX, deltaY);
        const distance = Math.min(maxDist, distToCursor * 0.15); // scaled constraint
        
        const pupilX = Math.cos(angle) * distance;
        const pupilY = Math.sin(angle) * distance;
        
        const pupil = eye.querySelector('.pupil');
        if (pupil) {
            pupil.style.transform = `translate(calc(-50% + ${pupilX}px), calc(-50% + ${pupilY}px))`;
        }
    });
});
