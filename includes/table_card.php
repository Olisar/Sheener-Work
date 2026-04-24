<?php
/* File: sheener/includes/table_card.php */

/**
 * Table Card Component
 * Reusable table card with header and search
 * 
 * Usage:
 * <?php
 *   $card_title = 'List Title';
 *   $card_icon = 'fas fa-list';
 *   $search_id = 'search-input';
 *   $search_placeholder = 'Search...';
 *   $table_content = '<table>...</table>';
 *   include 'includes/table_card.php';
 * ?>
 */
?>
<div class="table-card">
    <div class="standard-header">
        <h1><i class="<?php echo htmlspecialchars($card_icon ?? 'fas fa-list'); ?>"></i> <?php echo htmlspecialchars($card_title ?? 'List'); ?></h1>
        <?php if (isset($search_placeholder)): ?>
        <div class="standard-search">
            <input type="text" id="<?php echo htmlspecialchars($search_id ?? 'search'); ?>" 
                   placeholder="<?php echo htmlspecialchars($search_placeholder); ?>" />
        </div>
        <?php endif; ?>
    </div>
    <div class="task-table-container">
        <?php echo $table_content ?? ''; ?>
    </div>
</div>

