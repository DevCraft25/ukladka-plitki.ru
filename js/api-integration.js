/**
 * API Integration for Frontend
 * Укладка плитки - Database Integration
 */

const API_URL = '/api.php'; // API endpoint

// Load videos from database
async function loadVideosFromDatabase() {
    try {
        const response = await fetch(`${API_URL}?action=videos`);
        const data = await response.json();
        
        if (data.success && data.videos && data.videos.length > 0) {
            console.log('✅ Videos loaded from database:', data.videos.length);
            
            // Update videoData global variable
            window.videoData = data.videos.map(video => ({
                id: video.id,
                title: video.title,
                description: video.description,
                src: video.video_url,
                thumbnail: video.thumbnail_url,
                views: video.views
            }));
            
            // Reinitialize video player/grid if helper functions exist
            if (typeof initializeVideoPlayer === 'function') {
                initializeVideoPlayer();
            }

            if (typeof renderVideoGridFromDatabase === 'function') {
                renderVideoGridFromDatabase();
            }

            if (typeof syncHeroSlidesWithDatabase === 'function') {
                syncHeroSlidesWithDatabase();
            }
            
            // Track video views
            trackVideoViews();
            
            return true;
        } else {
            console.warn('⚠️ No videos in database, using default videos');
            return false;
        }
    } catch (error) {
        console.error('❌ Error loading videos:', error);
        return false;
    }
}

// Track video views
function trackVideoViews() {
    const mainVideo = document.getElementById('mainVideo');
    if (!mainVideo) return;
    
    let viewTracked = false;
    
    mainVideo.addEventListener('play', () => {
        if (!viewTracked && window.videoData && window.videoData[window.currentVideoIndex || 0]) {
            const currentVideo = window.videoData[window.currentVideoIndex || 0];
            
            // Track view after 3 seconds of watching
            setTimeout(() => {
                if (!mainVideo.paused) {
                    incrementVideoView(currentVideo.id);
                    viewTracked = true;
                }
            }, 3000);
        }
    });
}

// Increment video view count
async function incrementVideoView(videoId) {
    try {
        await fetch(`${API_URL}?action=video_view`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ video_id: videoId })
        });
        console.log('📊 View tracked for video:', videoId);
    } catch (error) {
        console.error('Error tracking view:', error);
    }
}

// Submit lead form to database
async function submitLeadToDatabase(formData) {
    try {
        const response = await fetch(`${API_URL}?action=lead`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log('✅ Lead submitted:', data.lead_id);
            return { success: true, message: data.message };
        } else {
            console.error('❌ Lead submission failed:', data.error);
            return { success: false, message: data.error };
        }
    } catch (error) {
        console.error('❌ Error submitting lead:', error);
        return { success: false, message: 'Ошибка отправки. Попробуйте позже.' };
    }
}

// Attach to contact forms
function attachLeadForms() {
    // Main contact form
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = {
                name: document.getElementById('contactName')?.value,
                phone: document.getElementById('contactPhone')?.value,
                email: document.getElementById('contactEmail')?.value,
                message: document.getElementById('contactMessage')?.value,
                source: 'contact_form'
            };
            
            const submitBtn = contactForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';
            
            const result = await submitLeadToDatabase(formData);
            
            if (result.success) {
                showNotification('✅ Заявка отправлена! Мы свяжемся с вами в ближайшее время.', 'success');
                contactForm.reset();
            } else {
                showNotification('❌ ' + result.message, 'error');
            }
            
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }, true);
    }
    
    // Premium bottom form on main page
    const premiumForm = document.getElementById('premiumForm');
    if (premiumForm) {
        premiumForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const nameInput = premiumForm.querySelector('input[name="name"]') || premiumForm.querySelector('#name');
            const phoneInput = premiumForm.querySelector('input[name="phone"]') || premiumForm.querySelector('#phone');
            const emailInput = premiumForm.querySelector('input[name="email"]') || premiumForm.querySelector('#email');
            const serviceSelect = premiumForm.querySelector('select[name="service"]') || premiumForm.querySelector('#service');
            const messageInput = premiumForm.querySelector('textarea[name="message"]') || premiumForm.querySelector('#message');

            const fd = new FormData(premiumForm);

            const name = ((nameInput?.value ?? fd.get('name')) || '').toString().trim();
            const phone = ((phoneInput?.value ?? fd.get('phone')) || '').toString().trim();
            const email = ((emailInput?.value ?? fd.get('email')) || '').toString().trim();
            const service = ((serviceSelect?.value ?? fd.get('service')) || '').toString();
            const message = ((messageInput?.value ?? fd.get('message')) || '').toString().trim();

            const submitBtn = premiumForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';

            const formData = {
                name,
                phone,
                email,
                message: service ? `[${service}] ${message || ''}` : message,
                source: 'premium_form'
            };

            console.log('premium debug', { name, phone, email, service, message, formData });

            const result = await submitLeadToDatabase(formData);

            if (result.success) {
                showNotification('✅ Заявка отправлена! Мы свяжемся с вами в ближайшее время.', 'success');
                premiumForm.reset();
            } else {
                showNotification('❌ ' + result.message, 'error');
            }

            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    }
    
}

// Show notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `api-notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 24px;
        right: 24px;
        max-width: 320px;
        background: rgba(0, 0, 0, 0.96);
        color: #ffffff;
        padding: 1rem 1.5rem;
        border-radius: 14px;
        border: 1px solid ${type === 'success' ? '#D4AF37' : '#f97373'};
        box-shadow: 0 18px 45px rgba(0,0,0,0.65);
        z-index: 999999;
        font-weight: 600;
        font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        letter-spacing: 0.02em;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        backdrop-filter: blur(16px);
        border-left: 3px solid ${type === 'success' ? '#D4AF37' : '#f97373'};
        text-shadow: 0 1px 2px rgba(0,0,0,0.6);
        animation: slideInRight 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

// Add notification animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 API Integration initialized');
    
    // Load videos from database
    loadVideosFromDatabase();
    
    // Attach lead forms
    attachLeadForms();
});

// Export functions for global use
window.loadVideosFromDatabase = loadVideosFromDatabase;
window.submitLeadToDatabase = submitLeadToDatabase;
