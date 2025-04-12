<?php
// Fetch reviews for this product
$reviews = $conn->query("
    SELECT r.*, u.name as reviewer_name, u.id as reviewer_id
    FROM reviews r
    JOIN users u ON r.consumer_id = u.id
    WHERE r.product_id = {$product['id']}
    ORDER BY r.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Calculate average rating
$avg_rating = 0;
if (!empty($reviews)) {
    $total_rating = array_sum(array_column($reviews, 'rating'));
    $avg_rating = round($total_rating / count($reviews), 1);
}
?>
            <div class="reviews-section" style="margin-top: 30px;">
                <h3 style="color: #2c3e50; margin-bottom: 20px;">
                    <i class="fas fa-star" style="color: #ffc107;"></i> 
                    Reviews (<?php echo count($reviews); ?>)
                    <?php if ($avg_rating > 0): ?>
                        <span style="font-size: 0.9em; color: #6c757d;">
                            • Average Rating: <?php echo $avg_rating; ?> / 5
                        </span>
                    <?php endif; ?>
                </h3>

                <?php if (empty($reviews)): ?>
                    <p style="color: #6c757d; text-align: center; padding: 20px;">
                        No reviews yet. Be the first to review this product!
                    </p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card" style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <div>
                                    <strong style="color: #2c3e50;">
                                        <?php echo htmlspecialchars($review['reviewer_name']); ?>
                                    </strong>
                                    <div style="color: #ffc107; margin: 5px 0;">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star" style="color: <?php echo $i <= $review['rating'] ? '#ffc107' : '#e9ecef'; ?>;"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <small style="color: #6c757d;">
                                    <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                </small>
                            </div>
                            <p style="color: #2c3e50; margin: 0;">
                                <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

<?php 