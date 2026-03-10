(function() {
    const { useEffect, useRef, useState } = wp.element;

    const BUNNY_GREETINGS = window.BUNNYHOPPER_GREETINGS && window.BUNNYHOPPER_GREETINGS.length > 0 
        ? window.BUNNYHOPPER_GREETINGS 
        : ["Hop hop! 🐰"];

    const HAS_PROMO_CODES = window.BUNNYHOPPER_PROMO_CODES && window.BUNNYHOPPER_PROMO_CODES.length > 0;

    function BunnyHopperApp() {
        const bunnyRef = useRef(null);
        const bodyRef = useRef(null);
        const earsRef = useRef(null);
        const tailRef = useRef(null);
        const shadowRef = useRef(null);
        const eggContainerRef = useRef(null);
        
        const [greeting, setGreeting] = useState(BUNNY_GREETINGS[0]);
        const [isHovered, setIsHovered] = useState(false);
        const [showPromo, setShowPromo] = useState(false);
        const [currentPromoCode, setCurrentPromoCode] = useState("");
        
        const isCodeRevealedRef = useRef(false);
        const clickCount = useRef(0);

        // Physics state
        const physics = useRef({
            x: window.innerWidth / 2,
            y: 0,
            vx: 0,
            vy: 0,
            scaleX: 1,
            scaleY: 1,
            earAngle: 0,
            tailAngle: 0,
            facingRight: true,
            state: 'idle', 
            timer: 60,
            dragStartX: 0,
            dragStartY: 0,
            eggs: []
        });

        // Spawns physical eggs
        const spawnEgg = (startX, startY, velX, velY) => {
            if (!eggContainerRef.current) return;
            const egg = document.createElement('div');
            egg.className = 'bh-easter-egg';
            const colors = ['#f472b6', '#60a5fa', '#34d399', '#fbbf24', '#a78bfa', '#ef4444', '#14b8a6'];
            egg.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            eggContainerRef.current.appendChild(egg);
            
            physics.current.eggs.push({
                x: startX,
                y: startY,
                vx: velX !== undefined ? velX : (Math.random() - 0.5) * 12,
                vy: velY !== undefined ? velY : -Math.random() * 8,
                rot: Math.random() * 360,
                el: egg
            });

            if (physics.current.eggs.length > 80) {
                const oldEgg = physics.current.eggs.shift();
                if (oldEgg && oldEgg.el) {
                    try {
                        if (oldEgg.el.parentNode === eggContainerRef.current) {
                            eggContainerRef.current.removeChild(oldEgg.el);
                        } else if (oldEgg.el.parentNode) {
                            oldEgg.el.remove();
                        }
                    } catch (e) {
                        // Silent catch for DOM inconsistencies
                    }
                }
            }
        };

        // Promo trigger logic
        const triggerPromo = () => {
            if (!HAS_PROMO_CODES) return;
            
            isCodeRevealedRef.current = true;
            setShowPromo(true);
            
            const randomIndex = Math.floor(Math.random() * window.BUNNYHOPPER_PROMO_CODES.length);
            const selectedCode = window.BUNNYHOPPER_PROMO_CODES[randomIndex];
            setCurrentPromoCode(selectedCode);
            setCurrentPromoCode(selectedCode);
            
            const p = physics.current;
            p.state = 'jumping';
            p.vy = -18; 
            p.vx = 0;
            
            let drops = 0;
            const dropInterval = setInterval(() => {
                spawnEgg(Math.random() * window.innerWidth, -50, (Math.random() - 0.5) * 5, Math.random() * 5);
                drops++;
                if (drops >= 40) clearInterval(dropInterval);
            }, 50);

            setTimeout(() => {
                setShowPromo(false);
                isCodeRevealedRef.current = false;
                clickCount.current = 0;
            }, 30000); 
        };

        // Main 60FPS Game Loop
        useEffect(() => {
            let rafId;
            const p = physics.current;
            const GRAVITY = 0.8;
            const GROUND = 0;
            const PADDING = 60; 

            const lerp = (start, end, amt) => (1 - amt) * start + amt * end;
            const clamp = (val, min, max) => Math.min(Math.max(val, min), max);

            const render = () => {
                const width = window.innerWidth;
                const height = window.innerHeight;
                const CEILING = -(height - 120); 

                // Promo pause state
                if (isCodeRevealedRef.current && (p.state === 'idle' || p.state === 'landed')) {
                    p.timer = 100; 
                    p.vx *= 0.8;   
                    p.scaleX = lerp(p.scaleX, 1, 0.1);
                    p.scaleY = lerp(p.scaleY, 1, 0.1);
                } 
                else if (p.state === 'drag') {
                    p.scaleX = lerp(p.scaleX, 0.85, 0.2);
                    p.scaleY = lerp(p.scaleY, 1.2, 0.2);
                    p.earAngle = lerp(p.earAngle, clamp(-p.vy * 2, -60, 60), 0.3);
                } 
                else {
                    if (p.state === 'idle') {
                        p.timer--;
                        p.scaleX = lerp(p.scaleX, 1 + Math.sin(Date.now() / 200) * 0.02, 0.1);
                        p.scaleY = lerp(p.scaleY, 1 - Math.sin(Date.now() / 200) * 0.02, 0.1);
                        
                        if (p.timer <= 0) {
                            p.state = 'jumping';
                            p.vy = -(Math.random() * 6 + 10); 
                            let speedX = Math.random() * 3 + 3;
                            if (p.x < PADDING) p.facingRight = true;
                            else if (p.x > width - PADDING) p.facingRight = false;
                            else if (Math.random() > 0.7) p.facingRight = !p.facingRight;
                            p.vx = p.facingRight ? speedX : -speedX;
                        }
                    } 
                    else if (p.state === 'landed') {
                        p.timer--;
                        p.scaleX = lerp(p.scaleX, 1, 0.15);
                        p.scaleY = lerp(p.scaleY, 1, 0.15);
                        p.vx *= 0.85;
                        p.x += p.vx;
                        if (p.timer <= 0) {
                            p.state = 'idle';
                            p.timer = Math.random() * 60 + 20; 
                        }
                    }

                    if (p.state === 'jumping' || p.y < GROUND) {
                        p.vy += GRAVITY;
                        p.x += p.vx;
                        p.y += p.vy;

                        p.scaleX = clamp(1 - p.vy * 0.006, 0.7, 1.3);
                        p.scaleY = clamp(1 + p.vy * 0.012, 0.7, 1.3);

                        if (p.x < PADDING) { p.x = PADDING; p.vx = Math.abs(p.vx) * 0.7; p.facingRight = true; }
                        if (p.x > width - PADDING) { p.x = width - PADDING; p.vx = -Math.abs(p.vx) * 0.7; p.facingRight = false; }
                        if (p.y < CEILING) { p.y = CEILING; p.vy = Math.abs(p.vy) * 0.5; }

                        if (p.y >= GROUND) {
                            p.y = GROUND;
                            if (p.vy > 6) {
                                p.vy = -p.vy * 0.55; 
                                p.vx *= 0.75; 
                                p.scaleX = 1 + (Math.abs(p.vy) * 0.04);
                                p.scaleY = 1 - (Math.abs(p.vy) * 0.03);
                            } else {
                                const impact = p.vy;
                                p.vy = 0;
                                p.state = 'landed';
                                p.timer = 15;
                                p.scaleX = 1 + (impact * 0.03);
                                p.scaleY = 1 - (impact * 0.02);
                            }
                        }
                    }

                    p.earAngle = lerp(p.earAngle, clamp(-p.vy * 3, -45, 40), 0.2);
                    p.tailAngle = lerp(p.tailAngle, clamp(p.vy * 2, -20, 20), 0.2);
                }

                // Eggs physics
                p.eggs.forEach((egg) => {
                    egg.vy += GRAVITY;
                    egg.x += egg.vx;
                    egg.y += egg.vy;
                    egg.rot += egg.vx * 2;

                    if (egg.x < 10) { egg.x = 10; egg.vx *= -0.8; }
                    if (egg.x > width - 10) { egg.x = width - 10; egg.vx *= -0.8; }
                    if (egg.y >= GROUND) {
                        egg.y = GROUND;
                        if (Math.abs(egg.vy) > 2) egg.vy *= -0.6; else egg.vy = 0;
                        egg.vx *= 0.95; 
                    }

                    const dx = egg.x - p.x;
                    const dy = egg.y - p.y;
                    const dist = Math.sqrt(dx*dx + dy*dy);
                    if (dist < 40) {
                        const force = (40 - dist) * 0.15;
                        egg.vx += (dx / dist) * force;
                        egg.vy += (dy / dist) * force - 2; 
                    }

                    if (egg.el) {
                        egg.el.style.transform = `translate3d(${egg.x}px, ${egg.y}px, 0) rotate(${egg.rot}deg)`;
                    }
                });

                // DOM Updates
                if (bunnyRef.current) {
                    bunnyRef.current.style.transform = `translate3d(${p.x}px, ${p.y}px, 0)`;
                    const dir = p.facingRight ? 1 : -1;
                    bodyRef.current.style.transform = `scale(${p.scaleX * dir}, ${p.scaleY})`;
                    earsRef.current.style.transform = `rotate(${p.earAngle}deg)`;
                    tailRef.current.style.transform = `rotate(${p.tailAngle}deg)`;

                    const altitude = Math.abs(p.y);
                    const shadowScale = clamp(1 - altitude / 150, 0.4, 1);
                    const shadowOp = clamp(0.12 - altitude / 400, 0.01, 0.12);
                    shadowRef.current.style.transform = `translate(-50%, 0) scaleX(${shadowScale})`;
                    shadowRef.current.style.opacity = shadowOp;
                }

                rafId = requestAnimationFrame(render);
            };

            render();
            return () => cancelAnimationFrame(rafId);
        }, []);

        // Input handling
        const handlePointerDown = (e) => {
            e.preventDefault(); 
            e.currentTarget.setPointerCapture(e.pointerId); 
            const p = physics.current;
            p.state = 'drag';
            p.dragStartX = e.clientX - p.x;
            p.dragStartY = e.clientY - p.y;
            p.vx = 0; p.vy = 0;
            
            spawnEgg(p.x, p.y - 40, (Math.random() - 0.5) * 12, -8 - Math.random() * 5);

            if (!isCodeRevealedRef.current && HAS_PROMO_CODES) {
                clickCount.current += 1;
                
                if (clickCount.current >= 3) {
                    triggerPromo();
                } else {
                    setIsHovered(true);
                    setGreeting(clickCount.current === 2 ? "One more! 🎁" : "Whoa! 🎢"); 
                }
            } else if (!isCodeRevealedRef.current && !HAS_PROMO_CODES) {
                // If no promos, just show random greeting on click without counting
                setIsHovered(true);
                setGreeting(BUNNY_GREETINGS[Math.floor(Math.random() * BUNNY_GREETINGS.length)]);
            }
        };

        const handlePointerMove = (e) => {
            const p = physics.current;
            if (p.state === 'drag') {
                const targetX = e.clientX - p.dragStartX;
                const targetY = e.clientY - p.dragStartY;
                p.vx = targetX - p.x;
                p.vy = targetY - p.y;
                p.x = targetX;
                p.y = targetY;
                if (p.vx > 1) p.facingRight = true;
                if (p.vx < -1) p.facingRight = false;
            }
        };

        const handlePointerUp = (e) => {
            e.currentTarget.releasePointerCapture(e.pointerId);
            const p = physics.current;
            if (p.state === 'drag') {
                p.state = 'jumping'; 
                p.vx = Math.min(Math.max(p.vx, -40), 40);
                p.vy = Math.min(Math.max(p.vy, -40), 40);
                if(!isCodeRevealedRef.current) {
                    setGreeting("Uff! 💨");
                    setTimeout(() => setIsHovered(false), 1500);
                }
            }
        };

        return (
            <div id="bh-root-wrapper">
                <style>{`
                    /* Scoped CSS for the interactive bunny */
                    #bh-root-wrapper {
                        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                        pointer-events: none; z-index: 999999; overflow: hidden;
                        font-family: system-ui, -apple-system, sans-serif;
                    }
                    #bh-root-wrapper * { box-sizing: border-box; }
                    .bh-entity {
                        position: absolute; bottom: 40px; left: 0;
                        width: 64px; height: 64px; margin-left: -32px;
                        transform-origin: bottom; pointer-events: auto;
                        cursor: grab; touch-action: none;
                        -webkit-tap-highlight-color: transparent;
                    }
                    .bh-entity:active { cursor: grabbing; }
                    
                    /* Mobile scaling and safe areas */
                    @media (max-width: 768px) {
                        .bh-entity {
                            width: 50px; height: 50px; margin-left: -25px;
                            bottom: 60px; /* Higher bottom for mobile nav */
                        }
                        .bh-tooltip {
                            bottom: 65px; padding: 6px 12px; font-size: 13px;
                        }
                        .bh-tooltip.bh-promo-mode {
                            bottom: 100px; padding: 12px 18px;
                        }
                        .bh-promo-value { font-size: 18px; }
                        .bh-credit-link { bottom: -20px; font-size: 9px; }
                    }

                    .bh-shadow {
                        position: absolute; bottom: -1px; left: 50%;
                        width: 40px; height: 6px; background-color: #64748b;
                        border-radius: 50%; filter: blur(2px);
                    }
                    .bh-tooltip {
                        position: absolute; bottom: 80px; left: 50%;
                        padding: 8px 16px; background-color: #1e293b; color: #ffffff;
                        font-size: 14px; font-weight: 600; border-radius: 12px;
                        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1);
                        transition: all 0.3s ease; transform-origin: bottom center;
                        white-space: nowrap; pointer-events: none;
                    }
                    .bh-tooltip.bh-show { opacity: 1; transform: translateX(-50%) scale(1); }
                    .bh-tooltip.bh-hide { opacity: 0; transform: translateX(-50%) scale(0.75); }
                    .bh-tooltip-arrow {
                        position: absolute; bottom: -6px; left: 50%; margin-left: -6px;
                        width: 12px; height: 12px; background-color: #1e293b; transform: rotate(45deg);
                    }
                    
                    .bh-tooltip.bh-promo-mode {
                        pointer-events: auto; bottom: 120px;
                        background-color: #ffffff; border: 3px solid #10b981;
                        padding: 16px 24px; z-index: 9999999;
                    }
                    .bh-tooltip.bh-promo-mode .bh-tooltip-arrow {
                        background-color: #ffffff;
                        border-bottom: 3px solid #10b981;
                        border-right: 3px solid #10b981;
                        bottom: -8px; width: 14px; height: 14px;
                    }
                    .bh-promo-code { display: flex; flex-direction: column; align-items: center; }
                    .bh-promo-instruction { font-size: 11px; color: #94a3b8; margin-top: 8px; font-weight: normal; }
                    .bh-promo-value {
                        font-size: 22px; font-weight: 900; color: #10b981;
                        background-color: #ecfdf5; padding: 4px 12px; border-radius: 6px;
                        user-select: all; cursor: text;
                    }

                    .bh-easter-egg {
                        position: absolute; bottom: 0; left: -10px; width: 20px; height: 26px;
                        border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
                        box-shadow: inset -3px -3px 6px rgba(0,0,0,0.2), 2px 2px 4px rgba(0,0,0,0.1);
                        border: 2px solid #334155; will-change: transform; pointer-events: none;
                    }
                    .bh-gpu {
                        transform: translate3d(0,0,0); will-change: transform; backface-visibility: hidden;
                    }
                    .bh-svg {
                        fill: #ffffff; stroke: #64748b; stroke-width: 2.2;
                        stroke-linecap: round; stroke-linejoin: round;
                    }
                `}</style>
                
                <div ref={eggContainerRef} style={{ position: 'absolute', bottom: '40px', left: 0, width: '100%', height: 0, pointerEvents: 'none' }} />

                <div 
                    ref={bunnyRef}
                    className="bh-entity bh-gpu"
                    onPointerDown={handlePointerDown}
                    onPointerMove={handlePointerMove}
                    onPointerUp={handlePointerUp}
                    onPointerCancel={handlePointerUp}
                    onContextMenu={(e) => e.preventDefault()} 
                    onMouseEnter={() => { if (physics.current.state !== 'drag' && !isCodeRevealedRef.current) { setIsHovered(true); setGreeting(BUNNY_GREETINGS[Math.floor(Math.random() * BUNNY_GREETINGS.length)]); } }}
                    onMouseLeave={() => { if (physics.current.state !== 'drag' && !isCodeRevealedRef.current) setIsHovered(false); }}
                >
                    <div ref={shadowRef} className="bh-shadow bh-gpu" />

                    <div className={`bh-tooltip ${isHovered || showPromo ? 'bh-show' : 'bh-hide'} ${showPromo ? 'bh-promo-mode' : ''}`}>
                        {showPromo ? (
                            <div className="bh-promo-code">
                                <span style={{color: '#64748b', marginBottom: '6px', fontSize: '13px'}}>Your discount code:</span>
                                <span className="bh-promo-value">{currentPromoCode}</span>
                                <span className="bh-promo-instruction">Copy and use at checkout (30s left)</span>
                            </div>
                        ) : greeting}
                        <div className="bh-tooltip-arrow"></div>
                    </div>

                    <div ref={bodyRef} className="bh-gpu" style={{ position: 'absolute', inset: 0, width: '100%', height: '100%', transformOrigin: 'bottom' }}>
                        <svg viewBox="0 0 100 100" style={{ width: '100%', height: '100%', overflow: 'visible' }}>
                            <g ref={tailRef} style={{ transformOrigin: '22px 76px' }}>
                                <circle cx="22" cy="76" r="6.5" className="bh-svg" />
                            </g>
                            <g ref={earsRef} style={{ transformOrigin: '70px 40px' }}>
                                <path d="M 65 40 Q 50 15 65 5 Q 75 10 75 40 Z" className="bh-svg" fill="#f8fafc" />
                                <path d="M 70 42 Q 65 10 80 5 Q 90 15 80 40 Z" className="bh-svg" />
                                <path d="M 72 38 Q 68 15 78 10 Q 85 20 78 35 Z" fill="#fbcfe8" stroke="none" />
                            </g>
                            <path d="M 45 85 C 30 85 25 75 45 70 Z" className="bh-svg" />
                            <path d="M 30 85 L 45 85" className="bh-svg" strokeWidth="2.5" />
                            <path d="M 75 40 C 95 40, 100 60, 90 70 C 80 80, 75 85, 65 85 L 45 85 C 25 85, 25 55, 50 50 C 65 45, 65 40, 75 40 Z" className="bh-svg" />
                            <path d="M 70 85 C 70 75 80 75 80 85 Z" className="bh-svg" />
                            <path d="M 65 85 L 85 85" className="bh-svg" strokeWidth="2.5" />
                            <circle cx="82" cy="55" r="2.5" fill="#334155" /> 
                            <circle cx="92" cy="58" r="1.5" fill="#f43f5e" /> 
                        </svg>
                    </div>
                </div>
            </div>
        );
    }

    // Safe initialization to prevent double-roots in Playground/Dynamic environments
    const initApp = () => {
        const rootElement = document.getElementById('bunnyhopper-root');
        if (rootElement && !window.bunnyHopperInitialized) {
            console.log("Easter Bunny Hopper: Initializing...");
            window.bunnyHopperInitialized = true;
            const root = ReactDOM.createRoot(rootElement);
            root.render(<BunnyHopperApp />);
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initApp);
    } else {
        initApp();
    }
})();
