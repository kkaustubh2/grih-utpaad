<?php

/**
 * Footer Template
 * Isolated styling that won't conflict with other pages
 */
?>
<footer class="grih-footer" style="
    background-color: #007B5E;
    color: white;
    padding: 40px 0 0;
    margin-top: 50px;
    position: relative;
    z-index: 100;
">
  <div class="footer-container" style="
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    ">
    <div class="footer-grid" style="
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
        ">
      <!-- Brand Column -->
      <div class="footer-col">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
          <i class="fas fa-leaf" style="font-size: 24px;"></i>
          <span style="font-size: 24px; font-weight: 600;">Grih Utpaad</span>
        </div>
        <p style="color: #e0f2f1; line-height: 1.6; margin: 0;">
          Empowering women entrepreneurs across India.
        </p>
        <div style="display: flex; gap: 15px; margin-top: 20px;">
          <a href="#" style="
                        color: white;
                        width: 36px;
                        height: 36px;
                        border-radius: 50%;
                        background: rgba(255,255,255,0.1);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        transition: all 0.3s;
                    "><i class="fab fa-facebook-f"></i></a>
          <a href="#" style="
                        color: white;
                        width: 36px;
                        height: 36px;
                        border-radius: 50%;
                        background: rgba(255,255,255,0.1);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        transition: all 0.3s;
                    "><i class="fab fa-instagram"></i></a>
          <a href="#" style="
                        color: white;
                        width: 36px;
                        height: 36px;
                        border-radius: 50%;
                        background: rgba(255,255,255,0.1);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        transition: all 0.3s;
                    "><i class="fab fa-youtube"></i></a>
        </div>
      </div>

      <!-- Links Column -->
      <div class="footer-col">
        <h3 style="
                    color: white;
                    font-size: 18px;
                    margin: 0 0 20px;
                    position: relative;
                    padding-bottom: 10px;
                ">
          Quick Links
          <span style="
                        position: absolute;
                        left: 0;
                        bottom: 0;
                        width: 30px;
                        height: 2px;
                        background: white;
                    "></span>
        </h3>
        <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px;">
          <li><a href="index.php" style="color: #e0f2f1; text-decoration: none;">Home</a></li>
          <li><a href="about.php" style="color: #e0f2f1; text-decoration: none;">About Us</a></li>
          <li><a href="products.php" style="color: #e0f2f1; text-decoration: none;">Products</a></li>
          <li><a href="contact.php" style="color: #e0f2f1; text-decoration: none;">Contact</a></li>
        </ul>
      </div>

      <!-- Newsletter Column -->
      <div class="footer-col">
        <h3 style="
                    color: white;
                    font-size: 18px;
                    margin: 0 0 20px;
                    position: relative;
                    padding-bottom: 10px;
                ">
          Newsletter
          <span style="
                        position: absolute;
                        left: 0;
                        bottom: 0;
                        width: 30px;
                        height: 2px;
                        background: white;
                    "></span>
        </h3>
        <p style="color: #e0f2f1; margin: 0 0 15px;">
          Subscribe for updates and exclusive offers.
        </p>
        <form style="display: flex; gap: 10px;">
          <input type="email" placeholder="Your email" style="
                        flex: 1;
                        padding: 12px;
                        border: none;
                        border-radius: 4px;
                        background: #006a50;
                        color: white;
                    ">
          <button type="submit" style="
                        padding: 0 20px;
                        background: #005944;
                        border: none;
                        border-radius: 4px;
                        color: white;
                        cursor: pointer;
                    ">
            <i class="fas fa-paper-plane"></i>
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Footer Bottom -->
  <div style="
        background: #005944;
        padding: 20px 0;
        margin-top: 40px;
    ">
    <div style="
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        ">
      <p style="color: #b2dfdb; margin: 0;">
        &copy; <?php echo date('Y'); ?> Grih Utpaad. All rights reserved.
      </p>
      <div style="display: flex; gap: 10px; align-items: center;">
        <p style="color: #b2dfdb; margin: 0;">
          Made with <i class="fas fa-heart" style="color: #ff6b6b;"></i> by
          <span style="color: white;">Kaustubh</span>,
          <span style="color: white;">Shivam</span>,
          <span style="color: white;">Akash</span>
        </p>
      </div>
      <div style="display: flex; gap: 15px;">
        <a href="privacy.php" style="color: #b2dfdb; text-decoration: none;">Privacy Policy</a>
        <span style="color: #007B5E;">|</span>
        <a href="terms.php" style="color: #b2dfdb; text-decoration: none;">Terms of Service</a>
      </div>
    </div>
  </div>
</footer>
