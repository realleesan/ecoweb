<?php
/**
 * Filter Component
 * 
 * Component lọc với nút "Lọc" và "Đặt lại"
 * 
 * @param string $type Loại filter: 'products', 'news', 'categories'
 * @param array $filter_fields Mảng các field filter với cấu trúc:
 *   [
 *     'type' => 'select|text|date', // Loại input
 *     'name' => 'field_name', // Tên field
 *     'label' => 'Nhãn hiển thị', // Nhãn
 *     'options' => [...], // Options cho select (nếu type = 'select')
 *     'placeholder' => '...', // Placeholder cho text/date
 *     'value' => '...' // Giá trị hiện tại
 *   ]
 * @param string $form_action URL action của form (mặc định: URL hiện tại)
 * @param array $preserve_params Mảng các query params cần giữ lại khi reset
 */

// Đảm bảo các tham số được truyền vào
if (!isset($type) || !in_array($type, ['products', 'news', 'categories'])) {
    return;
}

if (!isset($filter_fields) || !is_array($filter_fields) || empty($filter_fields)) {
    return;
}

// Xác định form action
if (!isset($form_action)) {
    $form_action = '';
}

if (!isset($preserve_params)) {
    $preserve_params = [];
}

// Lấy URL hiện tại để reset
$current_url = strtok($_SERVER["REQUEST_URI"], '?');
$reset_url = $current_url;
if (!empty($preserve_params)) {
    $preserved_query = [];
    foreach ($preserve_params as $param) {
        if (isset($_GET[$param])) {
            $preserved_query[$param] = $_GET[$param];
        }
    }
    if (!empty($preserved_query)) {
        $reset_url .= '?' . http_build_query($preserved_query);
    }
}
?>

<style>
    .filter-component {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 30px;
    }

    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        align-items: flex-end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
        flex: 1;
        min-width: 200px;
    }

    .filter-group label {
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 500;
        color: var(--dark);
        font-size: 14px;
    }

    .filter-group select,
    .filter-group input[type="text"],
    .filter-group input[type="date"] {
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-size: 14px;
        color: var(--dark);
        background-color: var(--white);
        width: 100%;
        transition: border-color 0.3s ease;
    }

    .filter-group select:focus,
    .filter-group input[type="text"]:focus,
    .filter-group input[type="date"]:focus {
        outline: none;
        border-color: var(--primary);
    }

    .filter-buttons {
        display: flex;
        gap: 10px;
        align-items: flex-end;
    }

    .filter-btn,
    .reset-btn {
        padding: 10px 25px;
        border: none;
        border-radius: 5px;
        font-family: '<?php echo FONT_FAMILY; ?>', sans-serif;
        font-weight: 500;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
    }

    .filter-btn {
        background-color: var(--primary);
        color: var(--white);
    }

    .filter-btn:hover {
        background-color: #2d4a2d;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .reset-btn {
        background-color: var(--white);
        color: var(--dark);
        border: 2px solid #ddd;
    }

    .reset-btn:hover {
        background-color: #f0f0f0;
        border-color: var(--dark);
    }

    @media (max-width: <?php echo BREAKPOINT_MD; ?>) {
        .filter-form {
            flex-direction: column;
        }

        .filter-group {
            width: 100%;
        }

        .filter-buttons {
            width: 100%;
        }

        .filter-btn,
        .reset-btn {
            flex: 1;
            justify-content: center;
        }
    }
</style>

<div class="filter-component">
    <form method="GET" action="<?php echo htmlspecialchars($form_action); ?>" class="filter-form" id="filterForm">
        <?php
        // Giữ lại các params cần thiết
        foreach ($preserve_params as $param) {
            if (isset($_GET[$param])) {
                echo '<input type="hidden" name="' . htmlspecialchars($param) . '" value="' . htmlspecialchars($_GET[$param]) . '">';
            }
        }
        
        // Render các filter fields
        foreach ($filter_fields as $field):
            $field_type = $field['type'] ?? 'select';
            $field_name = $field['name'] ?? '';
            $field_label = $field['label'] ?? '';
            $field_value = $field['value'] ?? '';
            $field_placeholder = $field['placeholder'] ?? '';
            $field_options = $field['options'] ?? [];
        ?>
            <div class="filter-group">
                <label for="filter-<?php echo htmlspecialchars($field_name); ?>">
                    <?php echo htmlspecialchars($field_label); ?>
                </label>
                
                <?php if ($field_type === 'select'): ?>
                    <select id="filter-<?php echo htmlspecialchars($field_name); ?>" 
                            name="<?php echo htmlspecialchars($field_name); ?>">
                        <?php foreach ($field_options as $option): 
                            $opt_value = is_array($option) ? $option['value'] : $option;
                            $opt_label = is_array($option) ? $option['label'] : $option;
                            $selected = ($field_value == $opt_value) ? 'selected' : '';
                        ?>
                            <option value="<?php echo htmlspecialchars($opt_value); ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($opt_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($field_type === 'text'): ?>
                    <input type="text" 
                           id="filter-<?php echo htmlspecialchars($field_name); ?>" 
                           name="<?php echo htmlspecialchars($field_name); ?>" 
                           placeholder="<?php echo htmlspecialchars($field_placeholder); ?>"
                           value="<?php echo htmlspecialchars($field_value); ?>">
                <?php elseif ($field_type === 'date'): ?>
                    <input type="date" 
                           id="filter-<?php echo htmlspecialchars($field_name); ?>" 
                           name="<?php echo htmlspecialchars($field_name); ?>" 
                           value="<?php echo htmlspecialchars($field_value); ?>">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <div class="filter-buttons">
            <button type="submit" class="filter-btn">
                <i class="fas fa-filter"></i>
                Lọc
            </button>
            <a href="<?php echo htmlspecialchars($reset_url); ?>" class="reset-btn">
                <i class="fas fa-redo"></i>
                Đặt lại
            </a>
        </div>
    </form>
</div>

