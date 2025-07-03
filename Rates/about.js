// About Page Interactive Features
class AboutPage {
    constructor() {
        this.init();
    }
    
    init() {
        this.setupCounterAnimation();
        this.setupScrollAnimations();
        this.setupTimelineInteractions();
        this.setupTeamMemberInteractions();
    }
    
    // Animated counter for statistics
    setupCounterAnimation() {
        const counters = document.querySelectorAll('.stat-number');
        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px 0px -100px 0px'
        };
        
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.animateCounter(entry.target);
                    counterObserver.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        counters.forEach(counter => {
            counterObserver.observe(counter);
        });
    }
    
    animateCounter(element) {
        const target = parseInt(element.getAttribute('data-target'));
        const duration = 2000; // 2 seconds
        const increment = target / (duration / 16); // 60fps
        let current = 0;
        
        const updateCounter = () => {
            current += increment;
            if (current < target) {
                // Handle decimal places for percentage values
                if (target < 100 && target % 1 !== 0) {
                    element.textContent = current.toFixed(1);
                } else {
                    element.textContent = Math.floor(current).toLocaleString();
                }
                requestAnimationFrame(updateCounter);
            } else {
                // Final value
                if (target < 100 && target % 1 !== 0) {
                    element.textContent = target.toFixed(1);
                } else {
                    element.textContent = target.toLocaleString();
                }
            }
        };
        
        updateCounter();
    }
    
    // Scroll-triggered animations
    setupScrollAnimations() {
        const animatedElements = document.querySelectorAll(
            '.mission-card, .timeline-item, .team-member, .culture-item, .award-item'
        );
        
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const scrollObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    // Add staggered delay for multiple elements
                    setTimeout(() => {
                        entry.target.classList.add('fade-in', 'visible');
                    }, index * 100);
                    
                    scrollObserver.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        animatedElements.forEach(element => {
            element.classList.add('fade-in');
            scrollObserver.observe(element);
        });
    }
    
    // Timeline interactions
    setupTimelineInteractions() {
        const timelineItems = document.querySelectorAll('.timeline-item');
        
        timelineItems.forEach((item, index) => {
            // Add click interaction for mobile
            item.addEventListener('click', () => {
                this.highlightTimelineItem(item);
            });
            
            // Add hover effects for desktop
            item.addEventListener('mouseenter', () => {
                this.highlightTimelineItem(item);
            });
            
            item.addEventListener('mouseleave', () => {
                this.removeTimelineHighlight(item);
            });
            
            // Staggered animation for timeline items
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            if (index % 2 === 0) {
                                entry.target.classList.add('slide-in-left', 'visible');
                            } else {
                                entry.target.classList.add('slide-in-right', 'visible');
                            }
                        }, index * 200);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.2 });
            
            observer.observe(item);
        });
    }
    
    highlightTimelineItem(item) {
        // Remove highlight from other items
        document.querySelectorAll('.timeline-item').forEach(otherItem => {
            if (otherItem !== item) {
                otherItem.style.opacity = '0.6';
            }
        });
        
        // Highlight current item
        item.style.opacity = '1';
        item.style.transform = 'scale(1.02)';
    }
    
    removeTimelineHighlight(item) {
        // Reset all items
        document.querySelectorAll('.timeline-item').forEach(timelineItem => {
            timelineItem.style.opacity = '1';
            timelineItem.style.transform = 'scale(1)';
        });
    }
    
    // Team member interactions
    setupTeamMemberInteractions() {
        const teamMembers = document.querySelectorAll('.team-member');
        
        teamMembers.forEach(member => {
            const memberPhoto = member.querySelector('.member-photo');
            const memberInfo = member.querySelector('.member-info');
            
            // Enhanced hover effects
            member.addEventListener('mouseenter', () => {
                this.highlightTeamMember(member);
            });
            
            member.addEventListener('mouseleave', () => {
                this.removeTeamMemberHighlight(member);
            });
            
            // Click to expand bio (mobile-friendly)
            member.addEventListener('click', () => {
                this.toggleMemberDetails(member);
            });
        });
    }
    
    highlightTeamMember(member) {
        // Dim other team members
        document.querySelectorAll('.team-member').forEach(otherMember => {
            if (otherMember !== member) {
                otherMember.style.opacity = '0.7';
                otherMember.style.transform = 'scale(0.98)';
            }
        });
        
        // Highlight current member
        member.style.opacity = '1';
        member.style.transform = 'scale(1.05)';
    }
    
    removeTeamMemberHighlight(member) {
        // Reset all team members
        document.querySelectorAll('.team-member').forEach(teamMember => {
            teamMember.style.opacity = '1';
            teamMember.style.transform = 'scale(1)';
        });
    }
    
    toggleMemberDetails(member) {
        const bio = member.querySelector('.member-bio');
        const credentials = member.querySelector('.member-credentials');
        
        if (bio.style.maxHeight && bio.style.maxHeight !== '0px') {
            // Collapse
            bio.style.maxHeight = '0px';
            bio.style.opacity = '0';
            credentials.style.maxHeight = '0px';
            credentials.style.opacity = '0';
        } else {
            // Expand
            bio.style.maxHeight = bio.scrollHeight + 'px';
            bio.style.opacity = '1';
            credentials.style.maxHeight = credentials.scrollHeight + 'px';
            credentials.style.opacity = '1';
        }
    }
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Smooth scrolling for internal links
function setupSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Parallax effect for hero section
function setupParallaxEffect() {
    const hero = document.querySelector('.about-hero');
    if (!hero) return;
    
    const handleScroll = debounce(() => {
        const scrolled = window.pageYOffset;
        const rate = scrolled * -0.5;
        hero.style.transform = `translateY(${rate}px)`;
    }, 10);
    
    window.addEventListener('scroll', handleScroll);
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AboutPage();
    setupSmoothScrolling();
    setupParallaxEffect();
    
    // Add loading animation
    document.body.classList.add('loaded');
});

// Handle window resize
window.addEventListener('resize', debounce(() => {
    // Recalculate any position-dependent elements
    const timelineItems = document.querySelectorAll('.timeline-item');
    timelineItems.forEach(item => {
        item.style.transform = '';
        item.style.opacity = '';
    });
}, 250));

// Export for potential use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AboutPage;
}