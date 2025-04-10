                            <td>
                                <?php if ($order['status'] === 'fulfilled'): ?>
                                    <a href="review_product.php?product_id=<?php echo $order['product_id']; ?>" 
                                       class="btn" style="background-color: #ffc107;">
                                        <i class="fas fa-star"></i> 
                                        <?php 
                                            $review_check = $conn->prepare("SELECT id FROM reviews WHERE product_id = ? AND consumer_id = ?");
                                            $review_check->bind_param("ii", $order['product_id'], $_SESSION['user']['id']);
                                            $review_check->execute();
                                            $has_review = $review_check->get_result()->num_rows > 0;
                                            echo $has_review ? 'Edit Review' : 'Add Review';
                                        ?>
                                    </a>
                                <?php endif; ?>
                            </td> 