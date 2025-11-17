<?php
$current_page = "student-payment";
$page_title = "Payment";
?>

<?php include '../../components/header.php'; ?>
<?php include '../../components/student-nav.php'; ?>
<!-- David (Latest Edits) -->
<div class="page-container">
    <div class="page-content">
        <!-- 1ST Section -->
        <section class="content-section">
            <div class="section-card-payment flex-col">
                <div class="size-fit group flex items-center gap-x-[10px] text-tertiary cursor-pointer">
                    <i class="ph ph-arrow-circle-left text-[24px] group-hover:hidden"></i>
                    <i class="ph-duotone ph-arrow-circle-left text-[24px] hidden group-hover:block"></i>
                    <p class="font-medium">Back</p>
                </div>
                <div class="w-full">
                    <?php include '../components/cards-template.php'; ?>
                </div>
                <p class="text-[21.6px] font-semibold">
                    Select your payment method
                </p>
                <!-- Payment Methods -->
                <div class="answers flex flex-col w-full gap-[25px]">
                    <!-- GCash -->
                    <div class="flex gap-[10px]">
                        <input type="radio" id="option1" name="payment-method" value="option1"
                            class="accent-tertiary duration-300 ease-in-out peer">
                        <label for="option1"
                            class="flex flex-grow items-center cursor-pointer p-4 rounded-[15px] bg-primary text-company_grey peer-checked:bg-tertiary/30 peer-checked:text-tertiary transition-colors duration-300 ease-in-out">
                            <img src="../images/GCash_logo.svg" alt="gcash logo" class="w-auto h-[30px]">
                        </label>
                    </div>
                    <!-- Maya -->
                    <div class="flex gap-[10px]">
                        <input type="radio" id="option2" name="payment-method" value="option2"
                            class="accent-tertiary duration-300 ease-in-out peer">
                        <label for="option2"
                            class="flex flex-grow items-center cursor-pointer p-4 rounded-[15px] bg-primary text-company_grey peer-checked:bg-tertiary/30 peer-checked:text-tertiary transition-colors duration-300 ease-in-out">
                            <img src="../images/Maya_logo.svg" alt="maya logo" class="w-auto h-[30px]">
                        </label>
                    </div>
                    <!-- Paypal -->
                    <div class="flex gap-[10px]">
                        <input type="radio" id="option3" name="payment-method" value="option3"
                            class="accent-tertiary duration-300 ease-in-out peer">
                        <label for="option3"
                            class="flex flex-grow items-center cursor-pointer p-4 rounded-[15px] bg-primary text-company_grey peer-checked:bg-tertiary/30 peer-checked:text-tertiary transition-colors duration-300 ease-in-out">
                            <img src="../images/Paypal_logo.svg" alt="paypal logo" class="w-auto h-[30px]">
                        </label>
                    </div>
                </div>
                <!-- Confirm Button -->
                <button type="submit" class="group btn-secondary">
                    <i class="ph ph-shopping-bag-open text-[24px] group-hover:hidden"></i>
                    <i class="ph-duotone ph-shopping-bag-open text-[24px] hidden group-hover:block"></i>
                    <p class="font-medium">Confirm Payment</p>
                </button>
            </div>
        </section>
    </div>
</div>

<?php include '../components/footer.php'; ?>

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<!-- JS -->
<script src="../components/navbar.js"></script>
<script src="../dist/javascript/scroll-to-top.js"></script>
<script src="../dist/javascript/carousel.js"></script>
<script src="../dist/javascript/user-dropdown.js"></script>
<!-- <script src="../dist/javascript/translate.js"></script> -->
</body>

</html>