<?php
require_once '../config/config.php';
require_once '../classes/Analytics.php';

$analytics = new Analytics();

// Get selected period from URL parameter
$selected_period = $_GET['period'] ?? 'month';
$valid_periods = ['today', 'week', 'month', 'quarter', 'year'];
if (!in_array($selected_period, $valid_periods)) {
    $selected_period = 'month';
}

// Get analytics data
$sales_analytics = $analytics->getSalesAnalytics($selected_period);
$sales_trend = $analytics->getSalesTrend('week', 7);
$top_products = $analytics->getTopSellingProducts(5, $selected_period);
$category_performance = $analytics->getCategoryPerformance($selected_period);
$customer_analytics = $analytics->getCustomerAnalytics($selected_period);
$order_status = $analytics->getOrderStatusDistribution($selected_period);
$inventory_insights = $analytics->getInventoryInsights();
$recent_activity = $analytics->getRecentActivity(8);
$comparison_data = $analytics->getComparisonData($selected_period);

$page_title = "Analytics Dashboard";
include 'includes/admin_header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Analytics Dashboard</h1>
            <p class="text-gray-600">Comprehensive business insights and performance metrics</p>
        </div>
        
        <!-- Period Selector -->
        <div class="flex items-center space-x-4">
            <label for="period-select" class="text-sm font-medium text-gray-700">Period:</label>
            <select id="period-select" onchange="changePeriod()" 
                    class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                <option value="today" <?= $selected_period === 'today' ? 'selected' : '' ?>>Today</option>
                <option value="week" <?= $selected_period === 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                <option value="month" <?= $selected_period === 'month' ? 'selected' : '' ?>>Last 30 Days</option>
                <option value="quarter" <?= $selected_period === 'quarter' ? 'selected' : '' ?>>Last 90 Days</option>
                <option value="year" <?= $selected_period === 'year' ? 'selected' : '' ?>>Last Year</option>
            </select>
        </div>
    </div>

    <!-- Key Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Revenue -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                    <p class="text-3xl font-bold text-gray-900">₦<?= number_format($sales_analytics['total_revenue'], 2) ?></p>
                    <?php if (isset($comparison_data['total_revenue'])): ?>
                    <div class="flex items-center mt-2">
                        <i class="fas fa-arrow-<?= $comparison_data['total_revenue']['trend'] === 'up' ? 'up text-green-500' : ($comparison_data['total_revenue']['trend'] === 'down' ? 'down text-red-500' : 'right text-gray-500') ?> mr-1"></i>
                        <span class="text-sm <?= $comparison_data['total_revenue']['trend'] === 'up' ? 'text-green-600' : ($comparison_data['total_revenue']['trend'] === 'down' ? 'text-red-600' : 'text-gray-600') ?>">
                            <?= abs(round($comparison_data['total_revenue']['change'], 1)) ?>% vs previous period
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-naira-sign text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Orders -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Orders</p>
                    <p class="text-3xl font-bold text-gray-900"><?= number_format($sales_analytics['total_orders']) ?></p>
                    <?php if (isset($comparison_data['total_orders'])): ?>
                    <div class="flex items-center mt-2">
                        <i class="fas fa-arrow-<?= $comparison_data['total_orders']['trend'] === 'up' ? 'up text-green-500' : ($comparison_data['total_orders']['trend'] === 'down' ? 'down text-red-500' : 'right text-gray-500') ?> mr-1"></i>
                        <span class="text-sm <?= $comparison_data['total_orders']['trend'] === 'up' ? 'text-green-600' : ($comparison_data['total_orders']['trend'] === 'down' ? 'text-red-600' : 'text-gray-600') ?>">
                            <?= abs(round($comparison_data['total_orders']['change'], 1)) ?>% vs previous period
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-shopping-cart text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Average Order Value -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Avg Order Value</p>
                    <p class="text-3xl font-bold text-gray-900">₦<?= number_format($sales_analytics['average_order_value'], 2) ?></p>
                    <?php if (isset($comparison_data['average_order_value'])): ?>
                    <div class="flex items-center mt-2">
                        <i class="fas fa-arrow-<?= $comparison_data['average_order_value']['trend'] === 'up' ? 'up text-green-500' : ($comparison_data['average_order_value']['trend'] === 'down' ? 'down text-red-500' : 'right text-gray-500') ?> mr-1"></i>
                        <span class="text-sm <?= $comparison_data['average_order_value']['trend'] === 'up' ? 'text-green-600' : ($comparison_data['average_order_value']['trend'] === 'down' ? 'text-red-600' : 'text-gray-600') ?>">
                            <?= abs(round($comparison_data['average_order_value']['change'], 1)) ?>% vs previous period
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-chart-line text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- New Customers -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">New Customers</p>
                    <p class="text-3xl font-bold text-gray-900"><?= number_format($sales_analytics['new_customers']) ?></p>
                    <?php if (isset($comparison_data['new_customers'])): ?>
                    <div class="flex items-center mt-2">
                        <i class="fas fa-arrow-<?= $comparison_data['new_customers']['trend'] === 'up' ? 'up text-green-500' : ($comparison_data['new_customers']['trend'] === 'down' ? 'down text-red-500' : 'right text-gray-500') ?> mr-1"></i>
                        <span class="text-sm <?= $comparison_data['new_customers']['trend'] === 'up' ? 'text-green-600' : ($comparison_data['new_customers']['trend'] === 'down' ? 'text-red-600' : 'text-gray-600') ?>">
                            <?= abs(round($comparison_data['new_customers']['change'], 1)) ?>% vs previous period
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                    <i class="fas fa-users text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Sales Trend Chart -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Sales Trend (Last 7 Days)</h3>
            <div class="h-64">
                <canvas id="salesTrendChart"></canvas>
            </div>
        </div>

        <!-- Order Status Distribution -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Order Status Distribution</h3>
            <div class="h-64">
                <canvas id="orderStatusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Products and Category Performance -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Top Selling Products -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Top Selling Products</h3>
            <div class="space-y-4">
                <?php if (empty($top_products)): ?>
                <p class="text-gray-500 text-center py-8">No sales data available for this period</p>
                <?php else: ?>
                <?php foreach ($top_products as $index => $product): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">
                            <?= $index + 1 ?>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900"><?= htmlspecialchars($product['name']) ?></p>
                            <p class="text-sm text-gray-500"><?= $product['total_sold'] ?> sold • ₦<?= number_format($product['total_revenue'], 2) ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-gray-900">₦<?= number_format($product['price'], 2) ?></p>
                        <p class="text-sm text-gray-500"><?= $product['order_count'] ?> orders</p>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Category Performance -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Category Performance</h3>
            <div class="space-y-4">
                <?php if (empty($category_performance)): ?>
                <p class="text-gray-500 text-center py-8">No category data available</p>
                <?php else: ?>
                <?php foreach (array_slice($category_performance, 0, 5) as $category): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-tag text-blue-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900"><?= htmlspecialchars($category['name']) ?></p>
                            <p class="text-sm text-gray-500"><?= $category['product_count'] ?> products • <?= $category['total_sold'] ?> sold</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-gray-900">₦<?= number_format($category['total_revenue'], 2) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Customer Analytics and Inventory Insights -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Customer Analytics -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Customer Analytics</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <p class="text-2xl font-bold text-blue-600"><?= number_format($customer_analytics['total_customers']) ?></p>
                    <p class="text-sm text-gray-600">Total Customers</p>
                </div>
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <p class="text-2xl font-bold text-green-600"><?= number_format($customer_analytics['active_customers']) ?></p>
                    <p class="text-sm text-gray-600">Active Customers</p>
                </div>
                <div class="text-center p-4 bg-purple-50 rounded-lg">
                    <p class="text-2xl font-bold text-purple-600">₦<?= number_format($customer_analytics['avg_lifetime_value'], 2) ?></p>
                    <p class="text-sm text-gray-600">Avg Lifetime Value</p>
                </div>
                <div class="text-center p-4 bg-orange-50 rounded-lg">
                    <p class="text-2xl font-bold text-orange-600"><?= number_format($customer_analytics['repeat_customer_rate'], 1) ?>%</p>
                    <p class="text-sm text-gray-600">Repeat Rate</p>
                </div>
            </div>
        </div>

        <!-- Inventory Insights -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Inventory Insights</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mr-3"></i>
                        <span class="text-gray-700">Low Stock Products</span>
                    </div>
                    <span class="font-semibold text-yellow-600"><?= $inventory_insights['low_stock_count'] ?></span>
                </div>
                
                <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-times-circle text-red-600 mr-3"></i>
                        <span class="text-gray-700">Out of Stock</span>
                    </div>
                    <span class="font-semibold text-red-600"><?= $inventory_insights['out_of_stock_count'] ?></span>
                </div>
                
                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-warehouse text-green-600 mr-3"></i>
                        <span class="text-gray-700">Total Inventory Value</span>
                    </div>
                    <span class="font-semibold text-green-600">₦<?= number_format($inventory_insights['total_inventory_value'], 2) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Activity</h3>
        <div class="space-y-3">
            <?php if (empty($recent_activity)): ?>
            <p class="text-gray-500 text-center py-8">No recent activity</p>
            <?php else: ?>
            <?php foreach ($recent_activity as $activity): ?>
            <div class="flex items-center justify-between p-3 border-l-4 <?= $activity['type'] === 'order' ? 'border-green-500 bg-green-50' : 'border-blue-500 bg-blue-50' ?>">
                <div class="flex items-center">
                    <i class="fas fa-<?= $activity['type'] === 'order' ? 'shopping-cart text-green-600' : 'user text-blue-600' ?> mr-3"></i>
                    <div>
                        <p class="text-gray-900"><?= htmlspecialchars($activity['description']) ?></p>
                        <p class="text-sm text-gray-500"><?= date('M j, Y g:i A', strtotime($activity['created_at'])) ?></p>
                    </div>
                </div>
                <?php if ($activity['amount']): ?>
                <span class="font-semibold text-gray-900">₦<?= number_format($activity['amount'], 2) ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Sales Trend Chart
const salesTrendCtx = document.getElementById('salesTrendChart').getContext('2d');
const salesTrendData = <?= json_encode($sales_trend) ?>;

new Chart(salesTrendCtx, {
    type: 'line',
    data: {
        labels: salesTrendData.map(item => item.formatted_date),
        datasets: [{
            label: 'Revenue (₦)',
            data: salesTrendData.map(item => item.revenue),
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Orders',
            data: salesTrendData.map(item => item.orders),
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Revenue (₦)'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Orders'
                },
                grid: {
                    drawOnChartArea: false,
                }
            }
        },
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        }
    }
});

// Order Status Chart
const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
const orderStatusData = <?= json_encode($order_status) ?>;

const statusColors = {
    'pending': '#f59e0b',
    'confirmed': '#3b82f6',
    'processing': '#8b5cf6',
    'shipped': '#06b6d4',
    'delivered': '#10b981',
    'completed': '#22c55e',
    'cancelled': '#ef4444'
};

new Chart(orderStatusCtx, {
    type: 'doughnut',
    data: {
        labels: orderStatusData.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1)),
        datasets: [{
            data: orderStatusData.map(item => item.count),
            backgroundColor: orderStatusData.map(item => statusColors[item.status] || '#6b7280'),
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return `${label}: ${value} (${percentage}%)`;
                    }
                }
            }
        }
    }
});

// Period selector
function changePeriod() {
    const period = document.getElementById('period-select').value;
    window.location.href = '?period=' + period;
}
</script>

<?php include 'includes/admin_footer.php'; ?> 