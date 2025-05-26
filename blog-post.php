<?php
require_once 'config/config.php';
require_once 'classes/Blog.php';

$blog = new Blog();

// Get blog post by slug
$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header('Location: blog.php');
    exit;
}

$post = $blog->getBySlug($slug);
if (!$post) {
    header('HTTP/1.0 404 Not Found');
    $page_title = "Post Not Found - Hi5ve MarketPlace";
    include 'includes/header.php';
    ?>
    <div class="min-h-screen bg-gray-50 py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <i class="fas fa-exclamation-triangle text-6xl text-gray-300 mb-6"></i>
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Blog Post Not Found</h1>
            <p class="text-xl text-gray-600 mb-8">The blog post you're looking for doesn't exist or has been removed.</p>
            <div class="space-x-4">
                <a href="blog.php" class="bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                    <i class="fas fa-blog mr-2"></i>View All Posts
                </a>
                <a href="/" class="bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-gray-700 transition duration-300">
                    <i class="fas fa-home mr-2"></i>Back to Home
                </a>
            </div>
        </div>
    </div>
    <?php
    include 'includes/footer.php';
    exit;
}

// Set page meta data
$page_title = htmlspecialchars($post['meta_title'] ?: $post['title']) . " - Hi5ve MarketPlace";
$meta_description = htmlspecialchars($post['meta_description'] ?: $post['excerpt'] ?: substr(strip_tags($post['content']), 0, 160));

// Get related posts (same author or recent posts)
$related_posts = $blog->getPublished(3, 0);
$related_posts = array_filter($related_posts, function($p) use ($post) {
    return $p['id'] != $post['id'];
});
$related_posts = array_slice($related_posts, 0, 3);

include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="flex items-center space-x-2 text-sm text-gray-500 mb-8">
            <a href="/" class="hover:text-green-600 transition duration-300">Home</a>
            <i class="fas fa-chevron-right"></i>
            <a href="blog.php" class="hover:text-green-600 transition duration-300">Blog</a>
            <i class="fas fa-chevron-right"></i>
            <span class="text-gray-900"><?= htmlspecialchars($post['title']) ?></span>
        </nav>

        <!-- Article -->
        <article class="bg-white rounded-lg shadow-md overflow-hidden">
            <!-- Featured Image -->
            <?php if ($post['featured_image']): ?>
            <div class="aspect-w-16 aspect-h-9">
                <img src="<?= htmlspecialchars($post['featured_image']) ?>" 
                     alt="<?= htmlspecialchars($post['title']) ?>"
                     class="w-full h-64 md:h-96 object-cover">
            </div>
            <?php endif; ?>
            
            <!-- Article Content -->
            <div class="p-8">
                <!-- Meta Info -->
                <div class="flex items-center text-sm text-gray-500 mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-user mr-2"></i>
                        <span><?= htmlspecialchars($post['first_name'] . ' ' . $post['last_name']) ?></span>
                    </div>
                    <span class="mx-3">•</span>
                    <div class="flex items-center">
                        <i class="fas fa-calendar mr-2"></i>
                        <span><?= date('F j, Y', strtotime($post['published_at'])) ?></span>
                    </div>
                    <span class="mx-3">•</span>
                    <div class="flex items-center">
                        <i class="fas fa-clock mr-2"></i>
                        <span><?= ceil(str_word_count(strip_tags($post['content'])) / 200) ?> min read</span>
                    </div>
                </div>
                
                <!-- Title -->
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
                    <?= htmlspecialchars($post['title']) ?>
                </h1>
                
                <!-- Excerpt -->
                <?php if ($post['excerpt']): ?>
                <div class="text-xl text-gray-600 mb-8 font-medium leading-relaxed">
                    <?= htmlspecialchars($post['excerpt']) ?>
                </div>
                <?php endif; ?>
                
                <!-- Content -->
                <div class="prose prose-lg max-w-none">
                    <?= nl2br(htmlspecialchars($post['content'])) ?>
                </div>
                
                <!-- Share Buttons -->
                <div class="border-t border-gray-200 pt-8 mt-12">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Share this post</h3>
                    <div class="flex space-x-4">
                        <a href="https://wa.me/?text=<?= urlencode($post['title'] . ' - ' . getCurrentUrl()) ?>" 
                           target="_blank"
                           class="bg-green-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                            <i class="fab fa-whatsapp mr-2"></i>WhatsApp
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(getCurrentUrl()) ?>" 
                           target="_blank"
                           class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition duration-300">
                            <i class="fab fa-facebook mr-2"></i>Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?text=<?= urlencode($post['title']) ?>&url=<?= urlencode(getCurrentUrl()) ?>" 
                           target="_blank"
                           class="bg-blue-400 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-500 transition duration-300">
                            <i class="fab fa-twitter mr-2"></i>Twitter
                        </a>
                        <button onclick="copyToClipboard('<?= getCurrentUrl() ?>')" 
                                class="bg-gray-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-gray-700 transition duration-300">
                            <i class="fas fa-link mr-2"></i>Copy Link
                        </button>
                    </div>
                </div>
            </div>
        </article>

        <!-- Related Posts -->
        <?php if (!empty($related_posts)): ?>
        <div class="mt-16">
            <h2 class="text-2xl font-bold text-gray-900 mb-8">Related Posts</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php foreach ($related_posts as $related_post): ?>
                <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-300">
                    <!-- Featured Image -->
                    <?php if ($related_post['featured_image']): ?>
                    <div class="aspect-w-16 aspect-h-9">
                        <img src="<?= htmlspecialchars($related_post['featured_image']) ?>" 
                             alt="<?= htmlspecialchars($related_post['title']) ?>"
                             class="w-full h-32 object-cover">
                    </div>
                    <?php else: ?>
                    <div class="w-full h-32 bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center">
                        <i class="fas fa-blog text-2xl text-white"></i>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Post Content -->
                    <div class="p-4">
                        <!-- Meta Info -->
                        <div class="text-xs text-gray-500 mb-2">
                            <?= date('M j, Y', strtotime($related_post['published_at'])) ?>
                        </div>
                        
                        <!-- Title -->
                        <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
                            <a href="blog-post.php?slug=<?= htmlspecialchars($related_post['slug']) ?>" 
                               class="hover:text-green-600 transition duration-300">
                                <?= htmlspecialchars($related_post['title']) ?>
                            </a>
                        </h3>
                        
                        <!-- Excerpt -->
                        <?php if ($related_post['excerpt']): ?>
                        <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                            <?= htmlspecialchars($related_post['excerpt']) ?>
                        </p>
                        <?php endif; ?>
                        
                        <!-- Read More -->
                        <a href="blog-post.php?slug=<?= htmlspecialchars($related_post['slug']) ?>" 
                           class="inline-flex items-center text-green-600 font-semibold text-sm hover:text-green-700 transition duration-300">
                            Read More
                            <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Call to Action -->
        <div class="bg-green-600 rounded-lg p-8 text-center text-white mt-16">
            <h3 class="text-2xl font-bold mb-4">Enjoyed this post?</h3>
            <p class="text-green-100 mb-6">Stay connected with us for more updates and exclusive offers</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="https://wa.me/<?= WHATSAPP_NUMBER ?>?text=Hi! I enjoyed reading your blog post about <?= urlencode($post['title']) ?>" 
                   target="_blank"
                   class="bg-white text-green-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                    <i class="fab fa-whatsapp mr-2"></i>Chat with Us
                </a>
                <a href="blog.php" 
                   class="bg-green-700 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-800 transition duration-300">
                    <i class="fas fa-blog mr-2"></i>Read More Posts
                </a>
                <a href="/" 
                   class="bg-green-700 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-800 transition duration-300">
                    <i class="fas fa-shopping-cart mr-2"></i>Shop Now
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.prose {
    color: #374151;
    line-height: 1.75;
}

.prose p {
    margin-bottom: 1.25em;
}

.prose h1, .prose h2, .prose h3, .prose h4, .prose h5, .prose h6 {
    color: #111827;
    font-weight: 600;
    margin-top: 2em;
    margin-bottom: 1em;
}

.prose h1 { font-size: 2.25em; }
.prose h2 { font-size: 1.875em; }
.prose h3 { font-size: 1.5em; }
.prose h4 { font-size: 1.25em; }

.prose ul, .prose ol {
    margin-bottom: 1.25em;
    padding-left: 1.625em;
}

.prose li {
    margin-bottom: 0.5em;
}

.prose blockquote {
    border-left: 4px solid #10b981;
    padding-left: 1em;
    margin: 1.5em 0;
    font-style: italic;
    color: #6b7280;
}

.prose strong {
    font-weight: 600;
    color: #111827;
}

.prose em {
    font-style: italic;
}

.prose code {
    background-color: #f3f4f6;
    padding: 0.125em 0.25em;
    border-radius: 0.25em;
    font-size: 0.875em;
}
</style>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Show temporary success message
        const btn = event.target;
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check mr-2"></i>Copied!';
        btn.classList.remove('bg-gray-600', 'hover:bg-gray-700');
        btn.classList.add('bg-green-600');
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('bg-green-600');
            btn.classList.add('bg-gray-600', 'hover:bg-gray-700');
        }, 2000);
    }).catch(function() {
        alert('Failed to copy link. Please copy manually: ' + text);
    });
}
</script>

<?php 
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

include 'includes/footer.php'; 
?> 