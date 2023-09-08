<?php

function all_labels_tab_content() {
    if (isset($_POST['delete_all_labels'])) {
        $upload_dir = wp_upload_dir();
        $dir_path = $upload_dir['basedir'];

        $patterns = array(
            'dpd-*.pdf',
        );

        foreach ($patterns as $pattern) {
            $files = glob($dir_path . '/*/*/' . $pattern);
            foreach ($files as $file) {
                unlink($file);
            }
        }

        wp_redirect('?page=express_label_maker&tab=all_labels');
        exit;
    }
    
    // prikaz svih labela
    $upload_dir = wp_upload_dir();
    $dir_path = $upload_dir['basedir'];
    $url_base = $upload_dir['baseurl'];

    // dodati prefiks kurira po potrebi
    $patterns = array(
        'dpd-*.pdf',
    );
    
    $files = array();
    foreach ($patterns as $pattern) {
        $files = array_merge($files, glob($dir_path . '/*/*/' . $pattern));
    }

    // paginacija
    $items_per_page = 150; // 30 stavki x 5 stupaca
    $current_page = isset($_GET['labels_page']) ? intval($_GET['labels_page']) : 1;
    $total_pages = ceil(count($files) / $items_per_page);
    $start_index = ($current_page - 1) * $items_per_page;
    $end_index = min($start_index + $items_per_page, count($files));

    echo '<div class="labels-grid">';
    for ($i = $start_index; $i < $end_index; $i++) {
        if (($i - $start_index) % 30 == 0) {
            echo $i != $start_index ? '</ul>' : ''; 
            echo '<ul>';
        }
        $file = $files[$i];
        $filename = basename($file);
        $file_url = $url_base . '/' . substr($file, strlen($dir_path) + 1);
        echo '<li><a href="' . $file_url . '" target="_blank">' . $filename . '</a></li>';
    }
    echo '</ul>';
    echo '</div>';

    if ($total_pages > 1) {
        echo '<div class="labels-pagination">';
        for ($i = 1; $i <= $total_pages; $i++) {
            $class = $i == $current_page ? 'current' : '';
            echo "<a href='?page=express_label_maker&tab=all_labels&labels_page=$i' class='$class'>$i</a>";
        }
        echo '</div>';
    }
    echo '<form method="post" action="" style="margin-top: 20px;">';
    echo '<input type="submit" name="delete_all_labels" value="Delete All" class="button button-delete" onclick="return confirm(\'Are you sure you want to delete all labels?\');">';
    echo '</form>';
}