    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div>
                    <div class="flex items-center mb-4">
                        <img src="assets/images/logo.png" alt="Hi5ve MarketPlace" class="h-8 w-auto mr-2">
                        <h3 class="text-xl font-bold">Hi5ve MarketPlace</h3>
                    </div>
                    <p class="text-gray-300 mb-4">
                        Your trusted grocery marketplace delivering fresh products to your doorstep across Nigeria.
                    </p>
                    <div class="flex space-x-4">
                        <a href="<?= getWhatsAppLink('Hello! I would like to know more about Hi5ve MarketPlace.') ?>" 
                           target="_blank" 
                           class="text-green-400 hover:text-green-300 transition duration-300">
                            <i class="fab fa-whatsapp text-2xl"></i>
                        </a>
                        <a href="#" class="text-blue-400 hover:text-blue-300 transition duration-300">
                            <i class="fab fa-facebook text-2xl"></i>
                        </a>
                        <a href="#" class="text-blue-400 hover:text-blue-300 transition duration-300">
                            <i class="fab fa-twitter text-2xl"></i>
                        </a>
                        <a href="#" class="text-pink-400 hover:text-pink-300 transition duration-300">
                            <i class="fab fa-instagram text-2xl"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="<?= SITE_URL ?>" class="text-gray-300 hover:text-white transition duration-300">Home</a></li>
                        <li><a href="products.php" class="text-gray-300 hover:text-white transition duration-300">All Products</a></li>
                        <li><a href="products.php?featured=1" class="text-gray-300 hover:text-white transition duration-300">Featured Products</a></li>
                        <li><a href="cart.php" class="text-gray-300 hover:text-white transition duration-300">Shopping Cart</a></li>
                        <?php if (isLoggedIn()): ?>
                        <li><a href="orders.php" class="text-gray-300 hover:text-white transition duration-300">My Orders</a></li>
                        <li><a href="profile.php" class="text-gray-300 hover:text-white transition duration-300">My Profile</a></li>
                        <?php else: ?>
                        <li><a href="login.php" class="text-gray-300 hover:text-white transition duration-300">Login</a></li>
                        <li><a href="register.php" class="text-gray-300 hover:text-white transition duration-300">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Categories -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">Categories</h4>
                    <ul class="space-y-2">
                        <?php
                        $footer_categories = (new Category())->getCategoriesWithProducts();
                        foreach (array_slice($footer_categories, 0, 6) as $cat):
                        ?>
                        <li>
                            <a href="products.php?category=<?= $cat['id'] ?>" 
                               class="text-gray-300 hover:text-white transition duration-300">
                                <?= htmlspecialchars($cat['name']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">Contact Us</h4>
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <i class="fab fa-whatsapp text-green-400 mr-3"></i>
                            <div>
                                <p class="text-gray-300">WhatsApp</p>
                                <a href="<?= getWhatsAppLink('Hello! I need assistance.') ?>" 
                                   target="_blank" 
                                   class="text-white hover:text-green-400 transition duration-300">
                                    <?= WHATSAPP_NUMBER ?>
                                </a>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <i class="fas fa-envelope text-blue-400 mr-3"></i>
                            <div>
                                <p class="text-gray-300">Email</p>
                                <a href="mailto:support@hi5ve.com" 
                                   class="text-white hover:text-blue-400 transition duration-300">
                                    support@hi5ve.com
                                </a>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <i class="fas fa-clock text-yellow-400 mr-3"></i>
                            <div>
                                <p class="text-gray-300">Business Hours</p>
                                <p class="text-white">Mon - Sat: 8AM - 8PM</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Banners -->
            <?php 
            if (file_exists(__DIR__ . '/banner_display.php')) {
                require_once __DIR__ . '/banner_display.php';
                displayBanners('footer');
            }
            ?>

            <!-- Payment Methods -->
            <div class="border-t border-gray-700 mt-8 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="mb-4 md:mb-0">
                        <h5 class="text-lg font-semibold mb-2">Payment Methods</h5>
                        <div class="flex space-x-4">
                            <div class="bg-white rounded px-3 py-2">
                                <span class="text-gray-800 font-semibold">Online Payment</span>
                            </div>
                            <div class="bg-green-600 rounded px-3 py-2">
                                <span class="text-white font-semibold">Cash on Delivery</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center md:text-right">
                        <h5 class="text-lg font-semibold mb-2">Order via WhatsApp</h5>
                        <a href="<?= getWhatsAppLink('Hello! I would like to place an order.') ?>" 
                           target="_blank" 
                           class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg transition duration-300 inline-flex items-center">
                            <i class="fab fa-whatsapp mr-2"></i>
                            Order Now
                        </a>
                    </div>
                </div>
            </div>

            <!-- Copyright -->
            <div class="border-t border-gray-700 mt-8 pt-8 text-center">
                <p class="text-gray-300">
                    &copy; <?= date('Y') ?> Hi5ve MarketPlace. All rights reserved. | 
                    Built with ❤️ for fresh grocery delivery in Nigeria
                </p>
                <p class="text-gray-400 text-sm mt-2">
                    Powered by PHP & MySQL | Currency: Nigerian Naira (₦)
                </p>
            </div>
        </div>
    </footer>

    <!-- Additional Scripts -->
    <script>
        // Hide loading screen when page is loaded
        window.addEventListener('load', function() {
            const loadingScreen = document.getElementById('loading-screen');
            if (loadingScreen) {
                setTimeout(() => {
                    loadingScreen.classList.add('fade-out');
                    setTimeout(() => {
                        loadingScreen.style.display = 'none';
                    }, 500);
                }, 500); // Show loading for at least 500ms
            }
        });

        // Smooth scrolling for anchor links
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

        // Auto-hide notifications
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html> 