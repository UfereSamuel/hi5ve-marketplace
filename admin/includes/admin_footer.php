        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-8">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <div class="text-sm text-gray-500">
                    © <?= date('Y') ?> Hi5ve MarketPlace. All rights reserved.
                </div>
                <div class="text-sm text-gray-500">
                    Admin Panel v1.0
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Confirm delete actions
        function confirmDelete(message = 'Are you sure you want to delete this item?') {
            return confirm(message);
        }

        // Show loading state for buttons
        function showLoading(button, text = 'Loading...') {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>' + text;
        }

        // Reset button state
        function resetButton(button, originalText) {
            button.disabled = false;
            button.innerHTML = originalText;
        }
    </script>
</body>
</html> 