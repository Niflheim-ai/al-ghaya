<!-- General Page Navigation Menu -->
<section class="bg-primary flex w-full items-center justify-center">
  <nav
    class="flex w-[1200px] items-center justify-start gap-[20px] px-[50px] py-[20px]"
  >
    <!-- Logo and Hamburger Button -->
    <div class="flex w-full items-center justify-between">
      <!-- Logo with Name -->
      <div class="flex h-fit w-full items-center gap-[15px]">
        <img
          src="../images/al-ghaya_logoForPrint.svg"
          alt="Al-Ghaya Logo"
          class="h-[50px] w-[50px]"
        />
        <h3 class="text-secondary text-[21.6px] font-bold">Al-Ghaya</h3>
      </div>
      <!-- Hamburger Button (Mobile/Tablet) -->
      <div id="hamburger-btn" class="text-secondary lg:hidden">
        <i class="ph ph-list text-3xl"></i>
      </div>
    </div>
    <!-- Navigation Links and Buttons -->
    <div
      id="nav-menu"
      class="hidden w-full gap-[30px] lg:flex lg:w-fit lg:items-center lg:justify-end"
    >
      <!-- Navigation Links -->
      <div
        class="text-secondary flex flex-col gap-[20px] text-[15px] font-semibold lg:flex-row"
      >
        <a
          href="../pages/sample.php"
          class="<?php echo ($current_page === 'home') ? 'text-tertiary underline' : 'hover:text-tertiary'; ?>"
          >Home</a
        >
        <a
          href="../pages/about.php"
          class="<?php echo ($current_page === 'about') ? 'text-tertiary underline' : 'hover:text-tertiary'; ?>"
          >About</a
        >
        <a
          href="../pages/programs.php"
          class="<?php echo ($current_page === 'programs') ? 'text-tertiary underline' : 'hover:text-tertiary'; ?>"
          >Programs</a
        >
        <a
          href="../pages/faculty.php"
          class="<?php echo ($current_page === 'faculty') ? 'text-tertiary underline' : 'hover:text-tertiary'; ?>"
          >Faculty</a
        >
      </div>
      <!-- Buttons -->
      <div class="mt-4 flex flex-col gap-[20px] lg:mt-0 lg:flex-row">
        <button
          class="bg-secondary text-primary rounded-[10px] px-[28px] py-[16px] text-[15px] font-medium text-nowrap"
        >
          Register Now
        </button>
        <button
          class="bg-tertiary2 text-tertiary border-tertiary hover:bg-tertiary hover:text-primary rounded-[10px] border-[1px] px-[28px] py-[16px] font-medium transition-colors duration-300 ease-in-out"
        >
          Login
        </button>
      </div>
    </div>
  </nav>
</section>
