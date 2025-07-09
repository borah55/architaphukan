// Video Player Application
class VideoPlayer {
    constructor() {
        this.videos = [
            {
                id: 1,
                title: "Archita Phukan Viral Video",
                src: "videos/archita-phukan-viral-video.mp4",
                duration: "1:34",
                views: "1.2M",
                uploadDate: "2 days ago",
                description: "An amazing viral video that has captured everyone's attention. Watch this incredible content that showcases talent and creativity.",
                thumbnail: null
            },
            {
                id: 2,
                title: "Latest Video Content",
                src: "videos/VID_20250709154938.mp4",
                duration: "0:10",
                views: "856K",
                uploadDate: "1 day ago",
                description: "Fresh new content that brings entertainment and joy. Don't miss this exciting video experience.",
                thumbnail: null
            }
        ];
        
        this.currentVideoIndex = 0;
        this.isPlaying = false;
        this.autoplay = true;
        
        this.initializeElements();
        this.setupEventListeners();
        this.loadVideoList();
        this.loadVideo(0);
        this.generateThumbnails();
    }
    
    initializeElements() {
        // Video player elements
        this.videoPlayer = document.getElementById('mainPlayer');
        this.playButton = document.getElementById('playButton');
        this.videoTitle = document.getElementById('videoTitle');
        this.videoViews = document.getElementById('videoViews');
        this.uploadDate = document.getElementById('uploadDate');
        this.videoDescription = document.getElementById('videoDescription');
        this.videoList = document.getElementById('videoList');
        
        // UI elements
        this.loadingSpinner = document.getElementById('loadingSpinner');
        this.mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
        this.mobileMenuClose = document.getElementById('mobileMenuClose');
        
        // Action buttons
        this.likeBtn = document.querySelector('.like-btn');
        this.dislikeBtn = document.querySelector('.dislike-btn');
        this.shareBtn = document.querySelector('.share-btn');
        this.saveBtn = document.querySelector('.save-btn');
        this.subscribeBtn = document.querySelector('.subscribe-btn');
        this.autoplayToggle = document.querySelector('.autoplay-toggle');
        
        // Search elements
        this.searchInput = document.querySelector('.search-input');
        this.searchBtn = document.querySelector('.search-btn');
    }
    
    setupEventListeners() {
        // Video player events
        this.videoPlayer.addEventListener('loadstart', () => this.showLoading());
        this.videoPlayer.addEventListener('canplay', () => this.hideLoading());
        this.videoPlayer.addEventListener('ended', () => this.onVideoEnded());
        this.videoPlayer.addEventListener('play', () => this.onVideoPlay());
        this.videoPlayer.addEventListener('pause', () => this.onVideoPause());
        this.videoPlayer.addEventListener('timeupdate', () => this.onTimeUpdate());
        this.videoPlayer.addEventListener('error', (e) => this.onVideoError(e));
        
        // Play button overlay
        this.playButton.addEventListener('click', () => this.togglePlayPause());
        
        // Action buttons
        this.likeBtn.addEventListener('click', () => this.toggleLike());
        this.dislikeBtn.addEventListener('click', () => this.toggleDislike());
        this.shareBtn.addEventListener('click', () => this.shareVideo());
        this.saveBtn.addEventListener('click', () => this.saveVideo());
        this.subscribeBtn.addEventListener('click', () => this.toggleSubscribe());
        this.autoplayToggle.addEventListener('click', () => this.toggleAutoplay());
        
        // Search functionality
        this.searchBtn.addEventListener('click', () => this.performSearch());
        this.searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.performSearch();
        });
        
        // Mobile menu
        this.mobileMenuClose.addEventListener('click', () => this.closeMobileMenu());
        this.mobileMenuOverlay.addEventListener('click', (e) => {
            if (e.target === this.mobileMenuOverlay) this.closeMobileMenu();
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => this.handleKeyboardShortcuts(e));
        
        // Responsive behavior
        window.addEventListener('resize', () => this.handleResize());
        
        // Prevent context menu on video
        this.videoPlayer.addEventListener('contextmenu', (e) => e.preventDefault());
    }
    
    loadVideo(index) {
        if (index < 0 || index >= this.videos.length) return;
        
        this.currentVideoIndex = index;
        const video = this.videos[index];
        
        this.showLoading();
        
        // Update video source
        this.videoPlayer.src = video.src;
        this.videoPlayer.load();
        
        // Update video information
        this.videoTitle.textContent = video.title;
        this.videoViews.textContent = video.views + ' views';
        this.uploadDate.textContent = video.uploadDate;
        this.videoDescription.textContent = video.description;
        
        // Update active video in list
        this.updateActiveVideoInList();
        
        // Update page title
        document.title = `${video.title} - VideoTube`;
        
        // Reset player state
        this.isPlaying = false;
        this.updatePlayButton();
    }
    
    loadVideoList() {
        this.videoList.innerHTML = '';
        
        this.videos.forEach((video, index) => {
            const videoItem = this.createVideoListItem(video, index);
            this.videoList.appendChild(videoItem);
        });
    }
    
    createVideoListItem(video, index) {
        const item = document.createElement('div');
        item.className = 'video-item';
        item.dataset.index = index;
        
        item.innerHTML = `
            <div class="video-thumbnail">
                <div class="thumbnail-placeholder" style="background: linear-gradient(45deg, #333, #555); display: flex; align-items: center; justify-content: center; color: #999; font-size: 12px;">
                    <i class="fas fa-play"></i>
                </div>
                <span class="video-duration">${video.duration}</span>
            </div>
            <div class="video-details">
                <h3 class="video-item-title">${video.title}</h3>
                <div class="video-item-meta">
                    <span>${video.views} views</span> • <span>${video.uploadDate}</span>
                </div>
            </div>
        `;
        
        item.addEventListener('click', () => {
            this.loadVideo(index);
            if (window.innerWidth <= 1024) {
                item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
        
        return item;
    }
    
    updateActiveVideoInList() {
        const videoItems = this.videoList.querySelectorAll('.video-item');
        videoItems.forEach((item, index) => {
            item.classList.toggle('active', index === this.currentVideoIndex);
        });
    }
    
    generateThumbnails() {
        // Generate thumbnails for videos using canvas
        this.videos.forEach((video, index) => {
            const tempVideo = document.createElement('video');
            tempVideo.src = video.src;
            tempVideo.crossOrigin = 'anonymous';
            tempVideo.muted = true;
            
            tempVideo.addEventListener('loadeddata', () => {
                tempVideo.currentTime = 1; // Seek to 1 second for thumbnail
            });
            
            tempVideo.addEventListener('seeked', () => {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                
                canvas.width = 120;
                canvas.height = 68;
                
                ctx.drawImage(tempVideo, 0, 0, canvas.width, canvas.height);
                
                const thumbnailUrl = canvas.toDataURL();
                this.updateThumbnail(index, thumbnailUrl);
            });
        });
    }
    
    updateThumbnail(index, thumbnailUrl) {
        const videoItem = this.videoList.children[index];
        if (videoItem) {
            const placeholder = videoItem.querySelector('.thumbnail-placeholder');
            if (placeholder) {
                placeholder.style.backgroundImage = `url(${thumbnailUrl})`;
                placeholder.style.backgroundSize = 'cover';
                placeholder.style.backgroundPosition = 'center';
                placeholder.innerHTML = '';
            }
        }
    }
    
    togglePlayPause() {
        if (this.videoPlayer.paused) {
            this.videoPlayer.play().catch(e => console.error('Play failed:', e));
        } else {
            this.videoPlayer.pause();
        }
    }
    
    onVideoPlay() {
        this.isPlaying = true;
        this.updatePlayButton();
    }
    
    onVideoPause() {
        this.isPlaying = false;
        this.updatePlayButton();
    }
    
    onVideoEnded() {
        this.isPlaying = false;
        this.updatePlayButton();
        
        if (this.autoplay && this.currentVideoIndex < this.videos.length - 1) {
            setTimeout(() => {
                this.loadVideo(this.currentVideoIndex + 1);
                this.videoPlayer.play().catch(e => console.error('Autoplay failed:', e));
            }, 1000);
        }
    }
    
    onTimeUpdate() {
        // Update progress if needed (for custom controls)
        const progress = (this.videoPlayer.currentTime / this.videoPlayer.duration) * 100;
        // Could be used for custom progress bar
    }
    
    onVideoError(e) {
        console.error('Video error:', e);
        this.hideLoading();
        this.showErrorMessage('Failed to load video. Please try again.');
    }
    
    updatePlayButton() {
        const icon = this.playButton.querySelector('i');
        if (this.isPlaying) {
            icon.className = 'fas fa-pause';
        } else {
            icon.className = 'fas fa-play';
        }
    }
    
    // Action button handlers
    toggleLike() {
        this.likeBtn.classList.toggle('active');
        if (this.likeBtn.classList.contains('active')) {
            this.dislikeBtn.classList.remove('active');
            this.showNotification('Added to liked videos');
        } else {
            this.showNotification('Removed from liked videos');
        }
    }
    
    toggleDislike() {
        this.dislikeBtn.classList.toggle('active');
        if (this.dislikeBtn.classList.contains('active')) {
            this.likeBtn.classList.remove('active');
            this.showNotification('Video disliked');
        } else {
            this.showNotification('Dislike removed');
        }
    }
    
    shareVideo() {
        const video = this.videos[this.currentVideoIndex];
        if (navigator.share) {
            navigator.share({
                title: video.title,
                text: video.description,
                url: window.location.href
            }).catch(e => console.error('Share failed:', e));
        } else {
            // Fallback: copy to clipboard
            navigator.clipboard.writeText(window.location.href).then(() => {
                this.showNotification('Link copied to clipboard');
            }).catch(() => {
                this.showNotification('Share feature not available');
            });
        }
    }
    
    saveVideo() {
        this.saveBtn.classList.toggle('active');
        if (this.saveBtn.classList.contains('active')) {
            this.showNotification('Video saved to watch later');
        } else {
            this.showNotification('Video removed from saved');
        }
    }
    
    toggleSubscribe() {
        this.subscribeBtn.classList.toggle('subscribed');
        if (this.subscribeBtn.classList.contains('subscribed')) {
            this.subscribeBtn.textContent = 'Subscribed';
            this.subscribeBtn.style.backgroundColor = '#666';
            this.showNotification('Subscribed to VideoTube Channel');
        } else {
            this.subscribeBtn.textContent = 'Subscribe';
            this.subscribeBtn.style.backgroundColor = '';
            this.showNotification('Unsubscribed from VideoTube Channel');
        }
    }
    
    toggleAutoplay() {
        this.autoplay = !this.autoplay;
        this.autoplayToggle.classList.toggle('active', this.autoplay);
        
        const icon = this.autoplayToggle.querySelector('i');
        if (this.autoplay) {
            icon.className = 'fas fa-toggle-on';
            this.showNotification('Autoplay enabled');
        } else {
            icon.className = 'fas fa-toggle-off';
            this.showNotification('Autoplay disabled');
        }
    }
    
    performSearch() {
        const query = this.searchInput.value.trim();
        if (query) {
            this.showNotification(`Searching for: ${query}`);
            // In a real application, this would perform actual search
            console.log('Search query:', query);
        }
    }
    
    handleKeyboardShortcuts(e) {
        // Prevent shortcuts when typing in input fields
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        
        switch (e.key.toLowerCase()) {
            case ' ':
            case 'k':
                e.preventDefault();
                this.togglePlayPause();
                break;
            case 'arrowleft':
                e.preventDefault();
                this.videoPlayer.currentTime = Math.max(0, this.videoPlayer.currentTime - 10);
                break;
            case 'arrowright':
                e.preventDefault();
                this.videoPlayer.currentTime = Math.min(this.videoPlayer.duration, this.videoPlayer.currentTime + 10);
                break;
            case 'arrowup':
                e.preventDefault();
                this.videoPlayer.volume = Math.min(1, this.videoPlayer.volume + 0.1);
                break;
            case 'arrowdown':
                e.preventDefault();
                this.videoPlayer.volume = Math.max(0, this.videoPlayer.volume - 0.1);
                break;
            case 'm':
                e.preventDefault();
                this.videoPlayer.muted = !this.videoPlayer.muted;
                break;
            case 'f':
                e.preventDefault();
                this.toggleFullscreen();
                break;
            case 'n':
                e.preventDefault();
                if (this.currentVideoIndex < this.videos.length - 1) {
                    this.loadVideo(this.currentVideoIndex + 1);
                }
                break;
            case 'p':
                e.preventDefault();
                if (this.currentVideoIndex > 0) {
                    this.loadVideo(this.currentVideoIndex - 1);
                }
                break;
        }
    }
    
    toggleFullscreen() {
        if (!document.fullscreenElement) {
            this.videoPlayer.requestFullscreen().catch(e => console.error('Fullscreen failed:', e));
        } else {
            document.exitFullscreen();
        }
    }
    
    handleResize() {
        // Handle responsive behavior
        if (window.innerWidth <= 768) {
            // Mobile adjustments
            this.adjustForMobile();
        } else {
            // Desktop adjustments
            this.adjustForDesktop();
        }
    }
    
    adjustForMobile() {
        // Mobile-specific adjustments
        const videoPlayerContainer = document.querySelector('.video-player-container');
        if (videoPlayerContainer) {
            videoPlayerContainer.style.aspectRatio = '16/9';
        }
    }
    
    adjustForDesktop() {
        // Desktop-specific adjustments
        const videoPlayerContainer = document.querySelector('.video-player-container');
        if (videoPlayerContainer) {
            videoPlayerContainer.style.aspectRatio = '16/9';
        }
    }
    
    closeMobileMenu() {
        this.mobileMenuOverlay.classList.remove('active');
    }
    
    showLoading() {
        this.loadingSpinner.classList.add('active');
    }
    
    hideLoading() {
        this.loadingSpinner.classList.remove('active');
    }
    
    showNotification(message) {
        // Create and show notification
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--bg-secondary);
            color: var(--text-primary);
            padding: 12px 20px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            z-index: 4000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            max-width: 300px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Remove after delay
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
    
    showErrorMessage(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.innerHTML = `
            <div style="
                background: var(--bg-secondary);
                border: 1px solid #ff4444;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                text-align: center;
                color: #ff4444;
            ">
                <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 10px;"></i>
                <p>${message}</p>
                <button onclick="location.reload()" style="
                    background: #ff4444;
                    color: white;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 4px;
                    margin-top: 10px;
                    cursor: pointer;
                ">Retry</button>
            </div>
        `;
        
        const videoSection = document.querySelector('.video-section');
        videoSection.insertBefore(errorDiv, videoSection.firstChild);
    }
}

// Additional utility functions
function formatDuration(seconds) {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = Math.floor(seconds % 60);
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
}

function formatViews(views) {
    if (views >= 1000000) {
        return (views / 1000000).toFixed(1) + 'M';
    } else if (views >= 1000) {
        return (views / 1000).toFixed(1) + 'K';
    }
    return views.toString();
}

function formatUploadDate(date) {
    const now = new Date();
    const uploadDate = new Date(date);
    const diffTime = Math.abs(now - uploadDate);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 1) return '1 day ago';
    if (diffDays < 7) return `${diffDays} days ago`;
    if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`;
    if (diffDays < 365) return `${Math.floor(diffDays / 30)} months ago`;
    return `${Math.floor(diffDays / 365)} years ago`;
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Initialize video player
    const videoPlayer = new VideoPlayer();
    
    // Add smooth scrolling for anchor links
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
    
    // Add intersection observer for animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observe elements for animation
    document.querySelectorAll('.video-item, .ad-space, .footer-section').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
    
    // Add loading states for images
    document.querySelectorAll('img').forEach(img => {
        img.addEventListener('load', function() {
            this.style.opacity = '1';
        });
        img.style.opacity = '0';
        img.style.transition = 'opacity 0.3s ease';
    });
    
    // Performance optimization: Lazy load video thumbnails
    const lazyLoadThumbnails = () => {
        const thumbnails = document.querySelectorAll('.video-thumbnail img[data-src]');
        thumbnails.forEach(img => {
            if (img.getBoundingClientRect().top < window.innerHeight + 100) {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
            }
        });
    };
    
    window.addEventListener('scroll', lazyLoadThumbnails);
    window.addEventListener('resize', lazyLoadThumbnails);
    
    // Add touch support for mobile
    let touchStartY = 0;
    let touchEndY = 0;
    
    document.addEventListener('touchstart', e => {
        touchStartY = e.changedTouches[0].screenY;
    });
    
    document.addEventListener('touchend', e => {
        touchEndY = e.changedTouches[0].screenY;
        handleSwipe();
    });
    
    function handleSwipe() {
        const swipeThreshold = 50;
        const diff = touchStartY - touchEndY;
        
        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                // Swipe up - could trigger next video
                console.log('Swipe up detected');
            } else {
                // Swipe down - could trigger previous video
                console.log('Swipe down detected');
            }
        }
    }
    
    console.log('VideoTube player initialized successfully');
});

