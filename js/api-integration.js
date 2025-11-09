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
            
            // Reinitialize video player if function exists
            if (typeof initializeVideoPlayer === 'function') {
                initializeVideoPlayer();
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
        });
    }
    
    // Callback form from video modal
    const videoCallbackBtn = document.getElementById('videoCallbackBtn');
    if (videoCallbackBtn) {
        videoCallbackBtn.addEventListener('click', async () => {
            const phone = prompt('Введите ваш номер телефона:');
            if (phone) {
                const result = await submitLeadToDatabase({
                    name: 'Заявка из видео',
                    phone: phone,
                    source: 'video_callback'
                });
                
                if (result.success) {
                    showNotification('✅ Спасибо! Мы вам перезвоним!', 'success');
                } else {
                    showNotification('❌ Ошибка. Попробуйте позже.', 'error');
                }
            }
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
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : '#ef4444'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        z-index: 999999;
        font-weight: 600;
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
