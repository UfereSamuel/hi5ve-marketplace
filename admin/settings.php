<?php
require_once '../config/config.php';
require_once '../classes/Settings.php';

$settings = new Settings();

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_settings':
                $update_data = [];
                
                // Process all settings
                foreach ($_POST as $key => $value) {
                    if ($key !== 'action' && strpos($key, 'setting_') === 0) {
                        $setting_key = substr($key, 8); // Remove 'setting_' prefix
                        $update_data[$setting_key] = sanitizeInput($value);
                    }
                }
                
                if ($settings->updateMultiple($update_data)) {
                    $success = 'Settings updated successfully!';
                } else {
                    $error = 'Failed to update settings';
                }
                break;
                
            case 'add_setting':
                $data = [
                    'setting_key' => sanitizeInput($_POST['setting_key']),
                    'setting_value' => sanitizeInput($_POST['setting_value']),
                    'setting_type' => sanitizeInput($_POST['setting_type']),
                    'category' => sanitizeInput($_POST['category']),
                    'description' => sanitizeInput($_POST['description'])
                ];
                
                if ($settings->create($data)) {
                    $success = 'Setting added successfully!';
                } else {
                    $error = 'Failed to add setting';
                }
                break;
                
            case 'delete_setting':
                $key = sanitizeInput($_POST['setting_key']);
                if ($settings->delete($key)) {
                    $success = 'Setting deleted successfully!';
                } else {
                    $error = 'Failed to delete setting';
                }
                break;
        }
    }
}

// Get all settings grouped by category
$grouped_settings = $settings->getGrouped();

$page_title = "Site Settings";
include 'includes/admin_header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Site Settings</h1>
            <p class="text-gray-600">Configure your website settings and preferences</p>
        </div>
        <button onclick="toggleAddForm()" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
            <i class="fas fa-plus mr-2"></i>Add Setting
        </button>
    </div>

    <?php if ($error): ?>
    <div class="alert mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
        <i class="fas fa-check-circle mr-2"></i>
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <!-- Add Setting Form -->
    <div id="add-setting-form" class="bg-white rounded-lg shadow-md p-6 mb-8 hidden">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">Add New Setting</h2>
        
        <form method="POST" action="" class="grid md:grid-cols-2 gap-6">
            <input type="hidden" name="action" value="add_setting">
            
            <div>
                <label for="setting_key" class="block text-sm font-medium text-gray-700 mb-1">Setting Key *</label>
                <input type="text" id="setting_key" name="setting_key" required
                       placeholder="e.g., site_name"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <div>
                <label for="setting_type" class="block text-sm font-medium text-gray-700 mb-1">Setting Type *</label>
                <select id="setting_type" name="setting_type" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="text">Text</option>
                    <option value="textarea">Textarea</option>
                    <option value="number">Number</option>
                    <option value="boolean">Boolean</option>
                    <option value="json">JSON</option>
                    <option value="file">File</option>
                </select>
            </div>
            
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                <select id="category" name="category" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="general">General</option>
                    <option value="contact">Contact</option>
                    <option value="shipping">Shipping</option>
                    <option value="inventory">Inventory</option>
                    <option value="features">Features</option>
                    <option value="tracking">Tracking</option>
                    <option value="social">Social Media</option>
                    <option value="custom">Custom</option>
                </select>
            </div>
            
            <div>
                <label for="setting_value" class="block text-sm font-medium text-gray-700 mb-1">Default Value</label>
                <input type="text" id="setting_value" name="setting_value"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            
            <div class="md:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea id="description" name="description" rows="2"
                          placeholder="Brief description of this setting"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
            </div>
            
            <div class="md:col-span-2 flex space-x-4">
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                    <i class="fas fa-save mr-2"></i>Add Setting
                </button>
                <button type="button" onclick="toggleAddForm()" class="bg-gray-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-gray-600 transition duration-300">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
            </div>
        </form>
    </div>

    <!-- Settings Form -->
    <form method="POST" action="">
        <input type="hidden" name="action" value="update_settings">
        
        <?php foreach ($grouped_settings as $category => $category_settings): ?>
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800 capitalize"><?= htmlspecialchars($category) ?> Settings</h2>
            </div>
            
            <div class="p-6 grid md:grid-cols-2 gap-6">
                <?php foreach ($category_settings as $setting): ?>
                <div class="<?= $setting['setting_type'] === 'textarea' ? 'md:col-span-2' : '' ?>">
                    <div class="flex justify-between items-start mb-1">
                        <label for="setting_<?= $setting['setting_key'] ?>" class="block text-sm font-medium text-gray-700">
                            <?= ucwords(str_replace('_', ' ', $setting['setting_key'])) ?>
                        </label>
                        
                        <!-- Delete button for custom settings -->
                        <?php if ($setting['category'] === 'custom'): ?>
                        <form method="POST" class="inline ml-2" onsubmit="return confirmDelete('Are you sure you want to delete this setting?')">
                            <input type="hidden" name="action" value="delete_setting">
                            <input type="hidden" name="setting_key" value="<?= $setting['setting_key'] ?>">
                            <button type="submit" class="text-red-600 hover:text-red-800 text-xs">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($setting['description']): ?>
                    <p class="text-xs text-gray-500 mb-2"><?= htmlspecialchars($setting['description']) ?></p>
                    <?php endif; ?>
                    
                    <?php
                    $input_name = "setting_{$setting['setting_key']}";
                    $input_value = htmlspecialchars($setting['setting_value']);
                    $input_class = "w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500";
                    
                    switch ($setting['setting_type']):
                        case 'textarea':
                            echo "<textarea id=\"{$input_name}\" name=\"{$input_name}\" rows=\"3\" class=\"{$input_class}\">{$input_value}</textarea>";
                            break;
                        case 'number':
                            echo "<input type=\"number\" id=\"{$input_name}\" name=\"{$input_name}\" value=\"{$input_value}\" class=\"{$input_class}\">";
                            break;
                        case 'boolean':
                            $checked = $setting['setting_value'] === '1' ? 'checked' : '';
                            echo "<div class=\"flex items-center\">";
                            echo "<input type=\"hidden\" name=\"{$input_name}\" value=\"0\">";
                            echo "<input type=\"checkbox\" id=\"{$input_name}\" name=\"{$input_name}\" value=\"1\" {$checked} class=\"rounded border-gray-300 text-green-600 shadow-sm focus:border-green-300 focus:ring focus:ring-green-200 focus:ring-opacity-50\">";
                            echo "<label for=\"{$input_name}\" class=\"ml-2 text-sm text-gray-700\">Enable</label>";
                            echo "</div>";
                            break;
                        case 'file':
                            echo "<div class=\"space-y-2\">";
                            if ($setting['setting_value']) {
                                echo "<div class=\"text-sm text-gray-600\">Current: {$input_value}</div>";
                            }
                            echo "<input type=\"file\" id=\"{$input_name}\" name=\"{$input_name}\" class=\"{$input_class}\">";
                            echo "<input type=\"hidden\" name=\"{$input_name}_current\" value=\"{$input_value}\">";
                            echo "</div>";
                            break;
                        default:
                            echo "<input type=\"text\" id=\"{$input_name}\" name=\"{$input_name}\" value=\"{$input_value}\" class=\"{$input_class}\">";
                    endswitch;
                    ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <!-- Save Button -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-end">
                <button type="submit" class="bg-green-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                    <i class="fas fa-save mr-2"></i>Save All Settings
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function toggleAddForm() {
    const form = document.getElementById('add-setting-form');
    form.classList.toggle('hidden');
    if (!form.classList.contains('hidden')) {
        document.getElementById('setting_key').focus();
    }
}
</script>

<?php include 'includes/admin_footer.php'; ?> 