document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Menu Toggle
    const mobileToggle = document.getElementById('mobile-toggle');
    const navMenu = document.querySelector('nav');

    if (mobileToggle && navMenu) {
        mobileToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });
    }

    // 2. Click Animation & Smooth Scroll for Internal Links
    const anchorLinks = document.querySelectorAll('a[href*="#"]');

    anchorLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            const targetId = href.substring(href.indexOf('#'));

            if (targetId && targetId !== '#') {
                const targetElement = document.querySelector(targetId);

                if (targetElement) {
                    e.preventDefault();

                    // Apply click animation effect
                    this.classList.add('click-animated');
                    setTimeout(() => this.classList.remove('click-animated'), 400);

                    // Close mobile nav if open
                    if (navMenu && navMenu.classList.contains('active')) {
                        navMenu.classList.remove('active');
                    }

                    // Smooth Scroll to destination
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
});

document.getElementById('contact-form').addEventListener('submit', async function (e) {
  e.preventDefault();

  const form = e.target;
  const statusDiv = document.getElementById('form-status');
  const submitBtn = form.querySelector('button[type="submit"]');

  submitBtn.disabled = true;
  statusDiv.textContent = 'Submitting query...';
  statusDiv.className = 'status-sending';

  try {
    // Relative path works across all subfolders
    const response = await fetch('/scripts/send-mail.php', {
      method: 'POST',
      body: new FormData(form)
    });

    const rawText = await response.text();
    let result;

    try {
      result = JSON.parse(rawText);
    } catch (parseErr) {
      throw new Error(`Server Response (${response.status}): ${rawText.substring(0, 120)}`);
    }

    if (response.ok && result.success) {
      statusDiv.textContent = result.message;
      statusDiv.className = 'status-success';
      form.reset();
    } else {
      statusDiv.textContent = result.message || 'Submission failed. Please try again.';
      statusDiv.className = 'status-error';
    }
  } catch (error) {
    statusDiv.textContent = `Error: ${error.message}`;
    statusDiv.className = 'status-error';
  } finally {
    submitBtn.disabled = false;
  }
});