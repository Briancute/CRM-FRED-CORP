<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1>Support Tickets</h1>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Ticket ID</th>
                <th>Email</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tickets)) : ?>
                <tr>
                    <td colspan="6">No tickets found.</td>
                </tr>
            <?php else : ?>
                <?php foreach ($tickets as $ticket) : ?>
                    <tr>
                        <td><?php echo esc_html($ticket->ticket_id); ?></td>
                        <td><?php echo esc_html($ticket->user_email); ?></td>
                        <td><?php echo esc_html($ticket->subject); ?></td>
                        <td>
                            <span class="ticket-status status-<?php echo esc_attr($ticket->status); ?>">
                                <?php echo esc_html(ucfirst($ticket->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($ticket->created_at))); ?></td>
                        <td>
                            <button type="button" 
                                    class="button view-ticket" 
                                    data-ticket-id="<?php echo esc_attr($ticket->ticket_id); ?>">
                                View Details
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Ticket Details Modal -->
    <div id="ticket-modal" class="ticket-modal" style="display: none;">
        <div class="ticket-modal-content">
            <span class="close">&times;</span>
            <h2>Ticket Details</h2>
            <div class="ticket-details">
                <p><strong>Ticket ID:</strong> <span id="modal-ticket-id"></span></p>
                <p><strong>Email:</strong> <span id="modal-email"></span></p>
                <p><strong>Subject:</strong> <span id="modal-subject"></span></p>
                <p><strong>Message:</strong></p>
                <div id="modal-message" class="ticket-message"></div>
                <p><strong>AI Response:</strong></p>
                <div id="modal-ai-response" class="ticket-ai-response"></div>
                <p><strong>Created:</strong> <span id="modal-created"></span></p>
            </div>
        </div>
    </div>

    <style>
        .ticket-status {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-open {
            background: #e7f0f7;
            color: #2271b1;
        }
        .status-closed {
            background: #f0f0f1;
            color: #50575e;
        }
        .ticket-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .ticket-modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 800px;
            border-radius: 5px;
            position: relative;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .ticket-message, .ticket-ai-response {
            background: #f0f0f1;
            padding: 15px;
            margin: 10px 0;
            border-radius: 3px;
            white-space: pre-wrap;
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        $('.view-ticket').on('click', function() {
            var ticketId = $(this).data('ticket-id');
            var row = $(this).closest('tr');
            
            $('#modal-ticket-id').text(ticketId);
            $('#modal-email').text(row.find('td:eq(1)').text());
            $('#modal-subject').text(row.find('td:eq(2)').text());
            $('#modal-created').text(row.find('td:eq(4)').text());
            
            // Get ticket details via AJAX
            $.post(ajaxurl, {
                action: 'wpai_get_ticket_details',
                ticket_id: ticketId,
                nonce: '<?php echo wp_create_nonce('wpai-admin-nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    $('#modal-message').text(response.data.message);
                    $('#modal-ai-response').text(response.data.ai_response);
                    $('#ticket-modal').show();
                }
            });
        });

        $('.close').on('click', function() {
            $('#ticket-modal').hide();
        });

        $(window).on('click', function(event) {
            if (event.target == $('#ticket-modal')[0]) {
                $('#ticket-modal').hide();
            }
        });
    });
    </script>
</div>
