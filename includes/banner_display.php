<?php
// Banner Display Component
// Usage: include this file and call displayBanners($position)

require_once __DIR__ . '/../classes/Banner.php';

function displayBanners($position = 'hero', $limit = null) {
    $banner = new Banner();
    $banners = $banner->getActiveByPosition($position);
    
    if (empty($banners)) {
        return;
    }
    
    // Limit banners if specified
    if ($limit) {
        $banners = array_slice($banners, 0, $limit);
    }
    
    // Record views for all displayed banners
    foreach ($banners as $banner_item) {
        $banner->recordView($banner_item['id']);
    }
    
    switch ($position) {
        case 'hero':
            displayHeroBanners($banners);
            break;
        case 'sidebar':
            displaySidebarBanners($banners);
            break;
        case 'footer':
            displayFooterBanners($banners);
            break;
        case 'category_top':
            displayCategoryTopBanners($banners);
            break;
        case 'popup':
            displayPopupBanners($banners);
            break;
        default:
            displayDefaultBanners($banners);
    }
}

function displayHeroBanners($banners) {
    if (count($banners) === 1) {
        // Single banner
        $banner_item = $banners[0];
        ?>
        <div class="hero-banner mb-8">
            <div class="relative overflow-hidden rounded-lg shadow-lg">
                <?php if ($banner_item['link_type'] !== 'none'): ?>
                <a href="<?= getBannerLink($banner_item) ?>" onclick="recordBannerClick(<?= $banner_item['id'] ?>)">
                <?php endif; ?>
                    <img src="<?= htmlspecialchars($banner_item['image_path']) ?>" 
                         alt="<?= htmlspecialchars($banner_item['title']) ?>"
                         class="w-full h-64 md:h-96 object-cover">
                    <?php if ($banner_item['title'] || $banner_item['description']): ?>
                    <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center">
                        <div class="text-center text-white p-6">
                            <?php if ($banner_item['title']): ?>
                            <h2 class="text-3xl md:text-5xl font-bold mb-4"><?= htmlspecialchars($banner_item['title']) ?></h2>
                            <?php endif; ?>
                            <?php if ($banner_item['description']): ?>
                            <p class="text-lg md:text-xl mb-6"><?= htmlspecialchars($banner_item['description']) ?></p>
                            <?php endif; ?>
                            <?php if ($banner_item['link_type'] !== 'none'): ?>
                            <button class="bg-green-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                                Shop Now
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php if ($banner_item['link_type'] !== 'none'): ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    } else {
        // Multiple banners - carousel
        ?>
        <div class="hero-carousel mb-8">
            <div class="relative overflow-hidden rounded-lg shadow-lg">
                <div class="carousel-container">
                    <?php foreach ($banners as $index => $banner_item): ?>
                    <div class="carousel-slide <?= $index === 0 ? 'active' : '' ?>" data-slide="<?= $index ?>">
                        <?php if ($banner_item['link_type'] !== 'none'): ?>
                        <a href="<?= getBannerLink($banner_item) ?>" onclick="recordBannerClick(<?= $banner_item['id'] ?>)">
                        <?php endif; ?>
                            <img src="<?= htmlspecialchars($banner_item['image_path']) ?>" 
                                 alt="<?= htmlspecialchars($banner_item['title']) ?>"
                                 class="w-full h-64 md:h-96 object-cover">
                            <?php if ($banner_item['title'] || $banner_item['description']): ?>
                            <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center">
                                <div class="text-center text-white p-6">
                                    <?php if ($banner_item['title']): ?>
                                    <h2 class="text-3xl md:text-5xl font-bold mb-4"><?= htmlspecialchars($banner_item['title']) ?></h2>
                                    <?php endif; ?>
                                    <?php if ($banner_item['description']): ?>
                                    <p class="text-lg md:text-xl mb-6"><?= htmlspecialchars($banner_item['description']) ?></p>
                                    <?php endif; ?>
                                    <?php if ($banner_item['link_type'] !== 'none'): ?>
                                    <button class="bg-green-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                                        Shop Now
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php if ($banner_item['link_type'] !== 'none'): ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($banners) > 1): ?>
                <!-- Carousel Controls -->
                <button class="carousel-prev absolute left-4 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-80 hover:bg-opacity-100 rounded-full p-2 shadow-lg">
                    <i class="fas fa-chevron-left text-gray-800"></i>
                </button>
                <button class="carousel-next absolute right-4 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-80 hover:bg-opacity-100 rounded-full p-2 shadow-lg">
                    <i class="fas fa-chevron-right text-gray-800"></i>
                </button>
                
                <!-- Carousel Indicators -->
                <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-2">
                    <?php foreach ($banners as $index => $banner_item): ?>
                    <button class="carousel-indicator w-3 h-3 rounded-full <?= $index === 0 ? 'bg-white' : 'bg-white bg-opacity-50' ?>" 
                            data-slide="<?= $index ?>"></button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

function displaySidebarBanners($banners) {
    ?>
    <div class="sidebar-banners space-y-4">
        <?php foreach ($banners as $banner_item): ?>
        <div class="banner-item">
            <?php if ($banner_item['link_type'] !== 'none'): ?>
            <a href="<?= getBannerLink($banner_item) ?>" onclick="recordBannerClick(<?= $banner_item['id'] ?>)">
            <?php endif; ?>
                <div class="relative overflow-hidden rounded-lg shadow-md hover:shadow-lg transition duration-300">
                    <img src="<?= htmlspecialchars($banner_item['image_path']) ?>" 
                         alt="<?= htmlspecialchars($banner_item['title']) ?>"
                         class="w-full h-32 object-cover">
                    <?php if ($banner_item['title']): ?>
                    <div class="absolute inset-0 bg-black bg-opacity-30 flex items-center justify-center">
                        <h3 class="text-white font-semibold text-center px-2"><?= htmlspecialchars($banner_item['title']) ?></h3>
                    </div>
                    <?php endif; ?>
                </div>
            <?php if ($banner_item['link_type'] !== 'none'): ?>
            </a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
}

function displayFooterBanners($banners) {
    ?>
    <div class="footer-banners grid grid-cols-1 md:grid-cols-<?= min(count($banners), 3) ?> gap-4">
        <?php foreach ($banners as $banner_item): ?>
        <div class="banner-item">
            <?php if ($banner_item['link_type'] !== 'none'): ?>
            <a href="<?= getBannerLink($banner_item) ?>" onclick="recordBannerClick(<?= $banner_item['id'] ?>)">
            <?php endif; ?>
                <div class="relative overflow-hidden rounded-lg shadow-md hover:shadow-lg transition duration-300">
                    <img src="<?= htmlspecialchars($banner_item['image_path']) ?>" 
                         alt="<?= htmlspecialchars($banner_item['title']) ?>"
                         class="w-full h-24 object-cover">
                    <?php if ($banner_item['title']): ?>
                    <div class="absolute inset-0 bg-black bg-opacity-30 flex items-center justify-center">
                        <h4 class="text-white font-semibold text-sm text-center px-2"><?= htmlspecialchars($banner_item['title']) ?></h4>
                    </div>
                    <?php endif; ?>
                </div>
            <?php if ($banner_item['link_type'] !== 'none'): ?>
            </a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
}

function displayCategoryTopBanners($banners) {
    ?>
    <div class="category-top-banners mb-6">
        <?php foreach ($banners as $banner_item): ?>
        <div class="banner-item mb-4">
            <?php if ($banner_item['link_type'] !== 'none'): ?>
            <a href="<?= getBannerLink($banner_item) ?>" onclick="recordBannerClick(<?= $banner_item['id'] ?>)">
            <?php endif; ?>
                <div class="relative overflow-hidden rounded-lg shadow-md">
                    <img src="<?= htmlspecialchars($banner_item['image_path']) ?>" 
                         alt="<?= htmlspecialchars($banner_item['title']) ?>"
                         class="w-full h-40 object-cover">
                    <?php if ($banner_item['title'] || $banner_item['description']): ?>
                    <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center">
                        <div class="text-center text-white p-4">
                            <?php if ($banner_item['title']): ?>
                            <h3 class="text-xl font-bold mb-2"><?= htmlspecialchars($banner_item['title']) ?></h3>
                            <?php endif; ?>
                            <?php if ($banner_item['description']): ?>
                            <p class="text-sm"><?= htmlspecialchars($banner_item['description']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php if ($banner_item['link_type'] !== 'none'): ?>
            </a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
}

function displayPopupBanners($banners) {
    foreach ($banners as $banner_item): ?>
    <div id="popup-banner-<?= $banner_item['id'] ?>" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden popup-banner">
        <div class="bg-white rounded-lg shadow-xl max-w-md mx-4 relative">
            <button onclick="closePopupBanner(<?= $banner_item['id'] ?>)" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 z-10">
                <i class="fas fa-times text-xl"></i>
            </button>
            <?php if ($banner_item['link_type'] !== 'none'): ?>
            <a href="<?= getBannerLink($banner_item) ?>" onclick="recordBannerClick(<?= $banner_item['id'] ?>)">
            <?php endif; ?>
                <img src="<?= htmlspecialchars($banner_item['image_path']) ?>" 
                     alt="<?= htmlspecialchars($banner_item['title']) ?>"
                     class="w-full rounded-lg">
            <?php if ($banner_item['link_type'] !== 'none'): ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach;
}

function displayDefaultBanners($banners) {
    ?>
    <div class="default-banners grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($banners as $banner_item): ?>
        <div class="banner-item">
            <?php if ($banner_item['link_type'] !== 'none'): ?>
            <a href="<?= getBannerLink($banner_item) ?>" onclick="recordBannerClick(<?= $banner_item['id'] ?>)">
            <?php endif; ?>
                <div class="relative overflow-hidden rounded-lg shadow-md hover:shadow-lg transition duration-300">
                    <img src="<?= htmlspecialchars($banner_item['image_path']) ?>" 
                         alt="<?= htmlspecialchars($banner_item['title']) ?>"
                         class="w-full h-48 object-cover">
                    <?php if ($banner_item['title'] || $banner_item['description']): ?>
                    <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center">
                        <div class="text-center text-white p-4">
                            <?php if ($banner_item['title']): ?>
                            <h3 class="text-lg font-bold mb-2"><?= htmlspecialchars($banner_item['title']) ?></h3>
                            <?php endif; ?>
                            <?php if ($banner_item['description']): ?>
                            <p class="text-sm"><?= htmlspecialchars($banner_item['description']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php if ($banner_item['link_type'] !== 'none'): ?>
            </a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php
}

function getBannerLink($banner) {
    switch ($banner['link_type']) {
        case 'product':
            return 'product.php?id=' . $banner['link_value'];
        case 'category':
            return 'category.php?id=' . $banner['link_value'];
        case 'url':
            return $banner['link_value'];
        default:
            return '#';
    }
}
?>

<script>
// Banner click tracking
function recordBannerClick(bannerId) {
    fetch('ajax/banner_click.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'banner_id=' + bannerId
    });
}

// Carousel functionality
document.addEventListener('DOMContentLoaded', function() {
    const carousel = document.querySelector('.carousel-container');
    if (!carousel) return;
    
    const slides = carousel.querySelectorAll('.carousel-slide');
    const prevBtn = document.querySelector('.carousel-prev');
    const nextBtn = document.querySelector('.carousel-next');
    const indicators = document.querySelectorAll('.carousel-indicator');
    
    let currentSlide = 0;
    
    function showSlide(index) {
        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === index);
        });
        indicators.forEach((indicator, i) => {
            indicator.classList.toggle('bg-white', i === index);
            indicator.classList.toggle('bg-white bg-opacity-50', i !== index);
        });
        currentSlide = index;
    }
    
    function nextSlide() {
        showSlide((currentSlide + 1) % slides.length);
    }
    
    function prevSlide() {
        showSlide((currentSlide - 1 + slides.length) % slides.length);
    }
    
    if (nextBtn) nextBtn.addEventListener('click', nextSlide);
    if (prevBtn) prevBtn.addEventListener('click', prevSlide);
    
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', () => showSlide(index));
    });
    
    // Auto-advance carousel
    if (slides.length > 1) {
        setInterval(nextSlide, 5000);
    }
});

// Popup banner functionality
function closePopupBanner(bannerId) {
    document.getElementById('popup-banner-' + bannerId).classList.add('hidden');
    localStorage.setItem('popup-banner-' + bannerId + '-closed', 'true');
}

// Show popup banners after page load
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        document.querySelectorAll('.popup-banner').forEach(popup => {
            const bannerId = popup.id.split('-')[2];
            if (!localStorage.getItem('popup-banner-' + bannerId + '-closed')) {
                popup.classList.remove('hidden');
            }
        });
    }, 2000); // Show after 2 seconds
});
</script>

<style>
.carousel-container {
    position: relative;
}

.carousel-slide {
    display: none;
    position: relative;
}

.carousel-slide.active {
    display: block;
}

.carousel-slide img {
    transition: transform 0.3s ease;
}

.carousel-slide:hover img {
    transform: scale(1.05);
}

.banner-item img {
    transition: transform 0.3s ease;
}

.banner-item:hover img {
    transform: scale(1.05);
}
</style> 