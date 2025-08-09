<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="wts-history-container">
        <div class="wts-filters">
            <form id="wts-history-filters" class="wts-filter-form">
                <div class="filter-group">
                    <label for="phone_filter">Phone Number:</label>
                    <input type="text" id="phone_filter" name="phone_filter" placeholder="Search by phone..." />
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="button">Filter</button>
                    <button type="button" id="clear-filters" class="button">Clear</button>
                </div>
            </form>
        </div>
        
        <div id="wts-history-loading" style="display: none;">
            <span class="spinner is-active"></span> Loading message history...
        </div>
        
        <div id="wts-history-content">
            <!-- History will be loaded here via AJAX -->
        </div>
        
        <div id="wts-pagination">
            <!-- Pagination will be loaded here -->
        </div>
    </div>
</div>

<style>
.wts-history-container {
    max-width: 1200px;
}

.wts-filters {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-bottom: 20px;
}

.wts-filter-form {
    display: flex;
    gap: 20px;
    align-items: end;
}

.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.filter-group input {
    width: 200px;
}

.wts-history-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 4px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.wts-history-table th,
.wts-history-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.wts-history-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #555;
}

.wts-history-table tr:hover {
    background: #f8f9fa;
}

.wts-status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-sent {
    background: #d4edda;
    color: #155724;
}

.status-failed {
    background: #f8d7da;
    color: #721c24;
}

.wts-parameters {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
}

.wts-phone {
    font-family: monospace;
    font-size: 13px;
}

.wts-date {
    white-space: nowrap;
    font-size: 13px;
    color: #666;
}

.wts-pagination {
    text-align: center;
    margin-top: 20px;
}

.wts-pagination .page-numbers {
    display: inline-block;
    padding: 8px 12px;
    margin: 0 2px;
    border: 1px solid #ddd;
    background: #fff;
    text-decoration: none;
    border-radius: 3px;
}

.wts-pagination .page-numbers:hover,
.wts-pagination .page-numbers.current {
    background: #0073aa;
    color: #fff;
    border-color: #0073aa;
}

.wts-no-results {
    text-align: center;
    padding: 40px;
    color: #666;
    background: #fff;
    border-radius: 4px;
}

#wts-history-loading {
    text-align: center;
    padding: 40px;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    let currentPage = 1;
    
    function loadHistory(page = 1, filters = {}) {
        $('#wts-history-loading').show();
        $('#wts-history-content').hide();
        
        $.post(wts_ajax.ajax_url, {
            action: 'wts_get_history',
            nonce: wts_ajax.nonce,
            page: page,
            phone_filter: filters.phone || ''
        }, function(response) {
            $('#wts-history-loading').hide();
            $('#wts-history-content').show().html(renderHistory(response));
            $('#wts-pagination').html(renderPagination(response, page));
        });
    }
    
    function renderHistory(response) {
        if (!response.success || !response.messages || response.messages.length === 0) {
            return '<div class="wts-no-results">No messages found.</div>';
        }
        
        let html = '<table class="wts-history-table">';
        html += '<thead><tr>';
        html += '<th>Date/Time</th>';
        html += '<th>Phone Number</th>';
        html += '<th>Template</th>';
        html += '<th>Parameters</th>';
        html += '<th>Status</th>';
        html += '<th>Sent By</th>';
        html += '</tr></thead>';
        html += '<tbody>';
        
        response.messages.forEach(function(message) {
            let parameters = '';
            try {
                let params = JSON.parse(message.parameters);
                parameters = params.join(', ');
            } catch (e) {
                parameters = message.parameters;
            }
            
            html += '<tr>';
            html += '<td class="wts-date">' + message.sent_at + '</td>';
            html += '<td class="wts-phone">' + message.phone_number + '</td>';
            html += '<td>' + message.template_name + '</td>';
            html += '<td class="wts-parameters" title="' + parameters + '">' + parameters + '</td>';
            html += '<td><span class="wts-status-badge status-' + message.status + '">' + message.status + '</span></td>';
            html += '<td>' + (message.sender_name || 'Unknown') + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        return html;
    }
    
    function renderPagination(response, currentPage) {
        if (!response.pages || response.pages <= 1) {
            return '';
        }
        
        let html = '<div class="wts-pagination">';
        
        if (currentPage > 1) {
            html += '<a href="#" class="page-numbers" data-page="' + (currentPage - 1) + '">&laquo; Previous</a>';
        }
        
        for (let i = 1; i <= response.pages; i++) {
            if (i === currentPage) {
                html += '<span class="page-numbers current">' + i + '</span>';
            } else {
                html += '<a href="#" class="page-numbers" data-page="' + i + '">' + i + '</a>';
            }
        }
        
        if (currentPage < response.pages) {
            html += '<a href="#" class="page-numbers" data-page="' + (currentPage + 1) + '">Next &raquo;</a>';
        }
        
        html += '</div>';
        return html;
    }
    
    // Load initial history
    loadHistory();
    
    // Handle filter form
    $('#wts-history-filters').on('submit', function(e) {
        e.preventDefault();
        currentPage = 1;
        loadHistory(1, {
            phone: $('#phone_filter').val()
        });
    });
    
    // Clear filters
    $('#clear-filters').on('click', function() {
        $('#phone_filter').val('');
        currentPage = 1;
        loadHistory();
    });
    
    // Handle pagination clicks
    $(document).on('click', '.page-numbers', function(e) {
        e.preventDefault();
        if ($(this).hasClass('current')) return;
        
        currentPage = parseInt($(this).data('page'));
        loadHistory(currentPage, {
            phone: $('#phone_filter').val()
        });
    });
});
</script>