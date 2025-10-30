<?php
// Fruit Information Website using FruityVice API
// Base URL: https://www.fruityvice.com/api/fruit/

// Initialize variables
$pageTitle = "All Fruits";
$searchQuery = "";
$fruits = [];
$error = null;
$selectedFruit = $_GET['fruit'] ?? '';
$showOnlySelected = isset($_GET['showOnly']);

// Function to fetch all fruits from API
function fetchAllFruits() {
    $url = "https://www.fruityvice.com/api/fruit/all";
    
    // Initialize cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'FruitInfo Website');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($response === false) {
        throw new Exception("Failed to connect to API: " . $curlError);
    }
    
    if ($httpCode !== 200) {
        throw new Exception("API returned HTTP $httpCode - Service may be unavailable");
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON response from API");
    }
    
    if (empty($data)) {
        throw new Exception("API returned empty data");
    }
    
    return $data;
}

// Function to get fruit color based on name
function getFruitColor($fruitName) {
    $fruitColors = [
        // Red Fruits
        'apple' => ['primary' => '#dc2626', 'secondary' => '#fecaca', 'accent' => '#ef4444'],
        'strawberry' => ['primary' => '#dc2626', 'secondary' => '#fecaca', 'accent' => '#ef4444'],
        'cherry' => ['primary' => '#dc2626', 'secondary' => '#fecaca', 'accent' => '#ef4444'],
        'raspberry' => ['primary' => '#dc2626', 'secondary' => '#fecaca', 'accent' => '#ef4444'],
        'watermelon' => ['primary' => '#dc2626', 'secondary' => '#bbf7d0', 'accent' => '#22c55e'],
        'pomegranate' => ['primary' => '#dc2626', 'secondary' => '#fecaca', 'accent' => '#ef4444'],
        
        // Orange Fruits
        'orange' => ['primary' => '#ea580c', 'secondary' => '#fed7aa', 'accent' => '#f97316'],
        'mandarin' => ['primary' => '#ea580c', 'secondary' => '#fed7aa', 'accent' => '#f97316'],
        'tangerine' => ['primary' => '#ea580c', 'secondary' => '#fed7aa', 'accent' => '#f97316'],
        'clementine' => ['primary' => '#ea580c', 'secondary' => '#fed7aa', 'accent' => '#f97316'],
        'apricot' => ['primary' => '#fdba74', 'secondary' => '#fed7aa', 'accent' => '#fb923c'],
        'mango' => ['primary' => '#f59e0b', 'secondary' => '#fef3c7', 'accent' => '#d97706'],
        
        // Yellow Fruits
        'banana' => ['primary' => '#eab308', 'secondary' => '#fef9c3', 'accent' => '#ca8a04'],
        'lemon' => ['primary' => '#eab308', 'secondary' => '#fef9c3', 'accent' => '#ca8a04'],
        'pineapple' => ['primary' => '#eab308', 'secondary' => '#fef9c3', 'accent' => '#ca8a04'],
        'passionfruit' => ['primary' => '#eab308', 'secondary' => '#fde047', 'accent' => '#ca8a04'],
        
        // Green Fruits
        'kiwi' => ['primary' => '#16a34a', 'secondary' => '#bbf7d0', 'accent' => '#22c55e'],
        'lime' => ['primary' => '#84cc16', 'secondary' => '#d9f99d', 'accent' => '#65a30d'],
        'avocado' => ['primary' => '#15803d', 'secondary' => '#bbf7d0', 'accent' => '#16a34a'],
        'green apple' => ['primary' => '#84cc16', 'secondary' => '#d9f99d', 'accent' => '#65a30d'],
        'pear' => ['primary' => '#84cc16', 'secondary' => '#d9f99d', 'accent' => '#65a30d'],
        'grape' => ['primary' => '#84cc16', 'secondary' => '#d9f99d', 'accent' => '#65a30d'],
        
        // Purple/Blue Fruits
        'blueberry' => ['primary' => '#7e22ce', 'secondary' => '#e9d5ff', 'accent' => '#a855f7'],
        'plum' => ['primary' => '#7e22ce', 'secondary' => '#e9d5ff', 'accent' => '#a855f7'],
        'grape' => ['primary' => '#7e22ce', 'secondary' => '#e9d5ff', 'accent' => '#a855f7'],
        'fig' => ['primary' => '#7e22ce', 'secondary' => '#e9d5ff', 'accent' => '#a855f7'],
        'blackberry' => ['primary' => '#7e22ce', 'secondary' => '#e9d5ff', 'accent' => '#a855f7'],
        
        // Brown/Tan Fruits
        'coconut' => ['primary' => '#a16207', 'secondary' => '#fef3c7', 'accent' => '#d97706'],
        'date' => ['primary' => '#a16207', 'secondary' => '#fef3c7', 'accent' => '#d97706'],
        
        // Pink Fruits
        'dragonfruit' => ['primary' => '#ec4899', 'secondary' => '#fce7f3', 'accent' => '#f472b6'],
        'guava' => ['primary' => '#ec4899', 'secondary' => '#fce7f3', 'accent' => '#f472b6'],
        'peach' => ['primary' => '#fdba74', 'secondary' => '#fed7aa', 'accent' => '#fb923c'],
    ];
    
    $name = strtolower($fruitName);
    
    // Check for exact matches first
    if (isset($fruitColors[$name])) {
        return $fruitColors[$name];
    }
    
    // Check for partial matches
    foreach ($fruitColors as $key => $colors) {
        if (strpos($name, $key) !== false) {
            return $colors;
        }
    }
    
    // Default colors for unknown fruits
    return ['primary' => '#800020', 'secondary' => '#1a1f1c', 'accent' => '#e8e8e8'];
}

// Get category and search parameters
$category = $_GET['category'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';

// Fetch data based on parameters
try {
    $allFruits = fetchAllFruits();
    
    // Apply filtering based on category and search
    if (!empty($searchQuery)) {
        // Search functionality
        $fruits = array_filter($allFruits, function($fruit) use ($searchQuery) {
            return stripos($fruit['name'], $searchQuery) !== false;
        });
        $fruits = array_values($fruits);
        $pageTitle = "Search Results for: " . htmlspecialchars($searchQuery);
    } else {
        // Category filtering based on actual API data
        switch($category) {
            case 'berries':
                $fruits = array_filter($allFruits, function($fruit) {
                    $berryNames = ['strawberry', 'blueberry', 'raspberry', 'blackberry', 'cranberry', 'boysenberry'];
                    $berryKeywords = ['berry'];
                    $name = strtolower($fruit['name']);
                    
                    foreach ($berryKeywords as $keyword) {
                        if (strpos($name, $keyword) !== false) {
                            return true;
                        }
                    }
                    return in_array($name, $berryNames);
                });
                $pageTitle = "Berries";
                break;
                
            case 'citrus':
                $fruits = array_filter($allFruits, function($fruit) {
                    $citrusNames = ['orange', 'lemon', 'lime', 'grapefruit', 'mandarin', 'tangerine', 'clementine', 'pomelo'];
                    $name = strtolower($fruit['name']);
                    $family = strtolower($fruit['family'] ?? '');
                    $genus = strtolower($fruit['genus'] ?? '');
                    
                    return in_array($name, $citrusNames) || 
                           strpos($family, 'rutaceae') !== false ||
                           strpos($genus, 'citrus') !== false;
                });
                $pageTitle = "Citrus Fruits";
                break;
                
            case 'tropical':
                $fruits = array_filter($allFruits, function($fruit) {
                    $tropicalNames = ['banana', 'pineapple', 'mango', 'papaya', 'coconut', 'avocado', 'guava', 'passion fruit', 'dragon fruit', 'lychee'];
                    $name = strtolower($fruit['name']);
                    return in_array($name, $tropicalNames);
                });
                $pageTitle = "Tropical Fruits";
                break;
                
            case 'stone':
                $fruits = array_filter($allFruits, function($fruit) {
                    $stoneNames = ['peach', 'plum', 'cherry', 'apricot', 'nectarine'];
                    $name = strtolower($fruit['name']);
                    $genus = strtolower($fruit['genus'] ?? '');
                    
                    return in_array($name, $stoneNames) || strpos($genus, 'prunus') !== false;
                });
                $pageTitle = "Stone Fruits";
                break;
                
            case 'melons':
                $fruits = array_filter($allFruits, function($fruit) {
                    $melonNames = ['watermelon', 'melon', 'cantaloupe', 'honeydew'];
                    $name = strtolower($fruit['name']);
                    $family = strtolower($fruit['family'] ?? '');
                    
                    foreach ($melonNames as $melon) {
                        if (strpos($name, $melon) !== false) {
                            return true;
                        }
                    }
                    return strpos($family, 'cucurbitaceae') !== false;
                });
                $pageTitle = "Melons";
                break;
                
            default:
                // All fruits
                $fruits = $allFruits;
                $pageTitle = "All Fruits";
                break;
        }
        
        $fruits = array_values($fruits);
    }
    
    // Sort fruits alphabetically
    if (is_array($fruits)) {
        usort($fruits, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
    }
    
} catch (Exception $e) {
    $error = "Unable to load fruit data from API: " . $e->getMessage();
    $fruits = [];
}

// If showOnlySelected is true and we have a selected fruit, filter the fruits array
if ($showOnlySelected && !empty($selectedFruit) && is_array($fruits)) {
    $filteredFruits = [];
    foreach($fruits as $fruit) {
        if ($fruit['name'] === $selectedFruit) {
            $filteredFruits[] = $fruit;
            break;
        }
    }
    $fruits = $filteredFruits;
    if (!empty($fruits)) {
        $pageTitle = htmlspecialchars($selectedFruit);
    }
}

// Handle empty results
if (empty($fruits) && !empty($searchQuery)) {
    $error = "No fruits found matching '" . htmlspecialchars($searchQuery) . "'";
} elseif (empty($fruits) && $category !== 'all') {
    $error = "No " . htmlspecialchars($category) . " fruits found in the database";
} elseif (empty($fruits)) {
    $error = "No fruits available in the database";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FruitInfo - <?php echo $pageTitle; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-[#0c0f0a] text-[#e8e8e8] font-inter">
    <!-- Scroll Progress Bar -->
    <div class="scroll-progress"></div>

    <!-- Navigation -->
    <nav class="bg-[#800020] border-b border-[#a00030] sticky top-0 z-50 transition-all duration-300 shadow-lg">
        <div class="container mx-auto px-4 py-3">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center justify-between w-full md:w-auto">
                    <div class="flex items-center">
                        <h1 class="text-2xl font-bold text-white">Fruit<span class="text-[#e8e8e8]">Info</span></h1>
                    </div>
                    
                    <!-- Mobile Menu Button -->
                    <button id="mobileMenuButton" class="md:hidden text-white p-2 rounded-lg hover:bg-[#600015] transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
                
                <!-- Search Bar -->
                <div class="w-full md:w-1/3 my-4 md:my-0">
                    <form id="searchForm" class="relative">
                        <input 
                            type="text" 
                            id="searchInput" 
                            name="search" 
                            placeholder="Search fruits..." 
                            class="w-full bg-[#600015] text-white rounded-lg py-2 px-4 focus:outline-none focus:ring-2 focus:ring-[#e8e8e8] border border-[#a00030] placeholder-gray-300"
                            value="<?php echo htmlspecialchars($searchQuery); ?>"
                        >
                        <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-300 hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </form>
                </div>
                
                <!-- Category Navigation -->
                <div id="navMenu" class="hidden md:flex flex-wrap justify-center gap-2 w-full md:w-auto">
                    <a href="?category=all" class="nav-btn <?php echo $category == 'all' && empty($searchQuery) && empty($selectedFruit) ? 'active' : ''; ?>">All Fruits</a>
                    <a href="?category=berries" class="nav-btn <?php echo $category == 'berries' ? 'active' : ''; ?>">Berries</a>
                    <a href="?category=citrus" class="nav-btn <?php echo $category == 'citrus' ? 'active' : ''; ?>">Citrus</a>
                    <a href="?category=tropical" class="nav-btn <?php echo $category == 'tropical' ? 'active' : ''; ?>">Tropical</a>
                    <a href="?category=stone" class="nav-btn <?php echo $category == 'stone' ? 'active' : ''; ?>">Stone Fruits</a>
                    <a href="?category=melons" class="nav-btn <?php echo $category == 'melons' ? 'active' : ''; ?>">Melons</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8 min-h-screen">
        <!-- Page Title and Actions -->
        <div class="mb-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                <div class="flex-1">
                    <h2 class="text-3xl font-bold text-[#e8e8e8] mb-2"><?php echo $pageTitle; ?></h2>
                    <p class="text-gray-400">
                        <?php if ($showOnlySelected && !empty($selectedFruit)): ?>
                            ✨ Viewing <?php echo htmlspecialchars($selectedFruit); ?> in detail
                        <?php else: ?>
                            👆 Click any fruit to view it in detail
                        <?php endif; ?>
                    </p>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex flex-wrap gap-3 mt-4 md:mt-0">
                    <?php if ($showOnlySelected && !empty($selectedFruit)): ?>
                        <button onclick="showAllFruits()" class="bg-[#800020] hover:bg-[#600015] text-white py-3 px-6 rounded-lg transition-all duration-300 font-medium flex items-center text-base">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            View All Fruits
                        </button>
                    <?php endif; ?>
                    
                    <?php if (isset($fruits) && is_array($fruits) && !empty($fruits) && !$error && !$showOnlySelected): ?>
                        <div class="bg-[#1a1f1c] rounded-lg px-4 py-2 border border-[#2a302c]">
                            <span class="text-sm text-gray-300">Showing </span>
                            <span class="text-[#e8e8e8] font-semibold"><?php echo count($fruits); ?></span>
                            <span class="text-sm text-gray-300"> fruits</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Error Message (if any) -->
        <?php if (isset($error)): ?>
            <div class="bg-yellow-900/30 border border-yellow-700 text-white px-4 py-6 rounded-lg mb-6 text-center">
                <div class="flex items-center justify-center mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mr-2 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-lg"><?php echo htmlspecialchars($error); ?></span>
                </div>
                <div class="mt-4">
                    <a href="?category=all" class="bg-[#800020] hover:bg-[#600015] text-white py-2 px-6 rounded-lg transition-colors inline-block">
                        View All Fruits
                    </a>
                </div>
                <div class="mt-3 text-sm text-gray-300">
                    <p>Data from <a href="https://fruityvice.com" class="text-[#e8e8e8] underline" target="_blank">FruityVice API</a></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Loading State -->
        <div id="loading" class="hidden flex justify-center items-center py-12">
            <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-[#e8e8e8] mx-auto mb-4"></div>
                <p class="text-gray-400">Loading fresh fruit data from API...</p>
            </div>
        </div>

        <!-- Fruit Grid -->
        <div id="fruitGrid" class="<?php echo $showOnlySelected && !empty($selectedFruit) ? 'single-fruit-view' : 'grid-view'; ?>">
            <?php if (isset($fruits) && is_array($fruits) && !empty($fruits)): ?>
                <?php foreach($fruits as $index => $fruit): ?>
                    <?php
                    $isSelected = $selectedFruit === $fruit['name'];
                    $fruitColors = getFruitColor($fruit['name']);
                    $cardClass = $isSelected ? 'fruit-card selected cursor-pointer' : 'fruit-card cursor-pointer';
                    $displayClass = $showOnlySelected && !$isSelected ? 'hidden' : '';
                    
                    // Add special class for single fruit view
                    if ($showOnlySelected && $isSelected) {
                        $cardClass .= ' single-fruit-card';
                    }
                    ?>
                    <div class="<?php echo $cardClass . ' ' . $displayClass; ?> fruit-grid-item rounded-xl overflow-hidden shadow-lg transition-all duration-300 hover:shadow-xl hover:-translate-y-2 border-2 <?php echo $isSelected ? 'border-[#e8e8e8] shadow-2xl' : 'border-opacity-30'; ?>"
                         data-fruit-name="<?php echo htmlspecialchars($fruit['name']); ?>"
                         data-fruit-color="<?php echo $fruitColors['primary']; ?>"
                         style="
                             background: linear-gradient(145deg, <?php echo $fruitColors['primary']; ?>15 0%, <?php echo $fruitColors['secondary']; ?>05 100%);
                             border-color: <?php echo $fruitColors['primary']; ?>50;
                         "
                         onclick="selectFruit('<?php echo htmlspecialchars($fruit['name']); ?>')">
                        <div class="p-5">
                            <!-- Fruit Header -->
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="<?php echo $showOnlySelected && $isSelected ? 'text-3xl' : 'text-xl'; ?> font-semibold fruit-name gradient-text"
                                    style="color: <?php echo $fruitColors['accent']; ?>">
                                    <?php echo htmlspecialchars($fruit['name']); ?>
                                </h3>
                                <span class="text-white text-xs font-medium px-2 py-1 rounded-full shadow-lg"
                                      style="background: <?php echo $fruitColors['primary']; ?>; border: 1px solid <?php echo $fruitColors['primary']; ?>70;">
                                    <?php echo isset($fruit['family']) ? htmlspecialchars($fruit['family']) : 'Fruit'; ?>
                                </span>
                            </div>
                            
                            <div class="space-y-3">
                                <!-- Nutrition Information -->
                                <div class="rounded-lg p-3 border"
                                     style="background: <?php echo $fruitColors['primary']; ?>10; border-color: <?php echo $fruitColors['primary']; ?>30;">
                                    <h4 class="<?php echo $showOnlySelected && $isSelected ? 'text-lg' : 'text-sm'; ?> font-medium mb-2 flex items-center"
                                        style="color: <?php echo $fruitColors['accent']; ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="<?php echo $showOnlySelected && $isSelected ? 'h-5 w-5' : 'h-4 w-4'; ?> mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                             style="color: <?php echo $fruitColors['primary']; ?>">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Nutrition (per 100g)
                                    </h4>
                                    <div class="grid grid-cols-2 gap-2 <?php echo $showOnlySelected && $isSelected ? 'text-base' : 'text-sm'; ?>">
                                        <div class="flex justify-between">
                                            <span class="text-opacity-80" style="color: <?php echo $fruitColors['accent']; ?>">Calories:</span>
                                            <span class="font-medium" style="color: <?php echo $fruitColors['accent']; ?>">
                                                <?php echo isset($fruit['nutritions']['calories']) ? htmlspecialchars($fruit['nutritions']['calories']) : 'N/A'; ?>
                                            </span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-opacity-80" style="color: <?php echo $fruitColors['accent']; ?>">Sugar:</span>
                                            <span class="font-medium" style="color: <?php echo $fruitColors['accent']; ?>">
                                                <?php echo isset($fruit['nutritions']['sugar']) ? htmlspecialchars($fruit['nutritions']['sugar']) . 'g' : 'N/A'; ?>
                                            </span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-opacity-80" style="color: <?php echo $fruitColors['accent']; ?>">Carbs:</span>
                                            <span class="font-medium" style="color: <?php echo $fruitColors['accent']; ?>">
                                                <?php echo isset($fruit['nutritions']['carbohydrates']) ? htmlspecialchars($fruit['nutritions']['carbohydrates']) . 'g' : 'N/A'; ?>
                                            </span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-opacity-80" style="color: <?php echo $fruitColors['accent']; ?>">Protein:</span>
                                            <span class="font-medium" style="color: <?php echo $fruitColors['accent']; ?>">
                                                <?php echo isset($fruit['nutritions']['protein']) ? htmlspecialchars($fruit['nutritions']['protein']) . 'g' : 'N/A'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Additional Info -->
                                <div class="flex justify-between <?php echo $showOnlySelected && $isSelected ? 'text-base' : 'text-sm'; ?>">
                                    <div class="flex items-center">
                                        <span class="text-opacity-80 mr-1" style="color: <?php echo $fruitColors['accent']; ?>">Order:</span>
                                        <span style="color: <?php echo $fruitColors['accent']; ?>"><?php echo isset($fruit['order']) ? htmlspecialchars($fruit['order']) : 'N/A'; ?></span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-opacity-80 mr-1" style="color: <?php echo $fruitColors['accent']; ?>">Genus:</span>
                                        <span style="color: <?php echo $fruitColors['accent']; ?>"><?php echo isset($fruit['genus']) ? htmlspecialchars($fruit['genus']) : 'N/A'; ?></span>
                                    </div>
                                </div>
                                
                                <!-- Additional Nutrition Info for Single View -->
                                <?php if ($showOnlySelected && $isSelected && isset($fruit['nutritions'])): ?>
                                    <div class="rounded-lg p-3 mt-4 border"
                                         style="background: <?php echo $fruitColors['primary']; ?>15; border-color: <?php echo $fruitColors['primary']; ?>40;">
                                        <h4 class="text-lg font-medium mb-2 flex items-center"
                                            style="color: <?php echo $fruitColors['accent']; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                 style="color: <?php echo $fruitColors['primary']; ?>">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                            Complete Nutritional Information
                                        </h4>
                                        <div class="grid grid-cols-2 gap-3 text-base">
                                            <?php foreach($fruit['nutritions'] as $key => $value): ?>
                                                <div class="flex justify-between">
                                                    <span class="text-opacity-80 capitalize" style="color: <?php echo $fruitColors['accent']; ?>"><?php echo htmlspecialchars($key); ?>:</span>
                                                    <span class="font-medium" style="color: <?php echo $fruitColors['accent']; ?>">
                                                        <?php echo htmlspecialchars($value); ?>
                                                        <?php echo in_array($key, ['sugar', 'carbohydrates', 'protein', 'fat']) ? 'g' : ''; ?>
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Click Indicator (only show in grid view) -->
                                <?php if (!$showOnlySelected || !$isSelected): ?>
                                    <div class="pt-2 border-t text-center"
                                         style="border-color: <?php echo $fruitColors['primary']; ?>30;">
                                        <div class="flex items-center justify-center text-xs click-indicator"
                                             style="color: <?php echo $fruitColors['primary']; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Click to view in detail
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php elseif (!isset($error)): ?>
                <!-- Empty State -->
                <div class="col-span-full text-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-xl font-medium text-[#e8e8e8] mb-2">No fruits found</h3>
                    <p class="text-gray-400 mb-4">Try a different search term or browse by category</p>
                    <a href="?category=all" class="nav-btn inline-block">Browse All Fruits</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-[#1a1f1c] border-t border-[#2a302c] py-8">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <h2 class="text-xl font-bold text-[#e8e8e8]">Fruit<span class="text-[#800020]">Info</span></h2>
                    <p class="text-gray-400 text-sm mt-1">Your comprehensive source for fruit information</p>
                </div>
                <div class="text-gray-400 text-sm">
                    <p>Live data from <a href="https://fruityvice.com" class="text-[#e8e8e8] hover:text-white transition-colors" target="_blank">FruityVice API</a></p>
                </div>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>