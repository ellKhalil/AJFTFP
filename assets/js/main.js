/**
 * African Journal of Finance, Taxation, and Fiscal Policy (AJFTFP)
 * Main JavaScript Engine
 */

document.addEventListener('DOMContentLoaded', () => {
  // Update current year in footer
  const currentYearEls = document.querySelectorAll('.current-year');
  currentYearEls.forEach(el => {
    el.textContent = new Date().getFullYear();
  });

  // Initialize Bootstrap Tooltips if any
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

  // -------------------------------------------------------------
  // 1. Interactive Citation Generator
  // -------------------------------------------------------------
  const citationFormatSelect = document.getElementById('citationFormat');
  const citationTextEl = document.getElementById('citationOutput');
  const copyCitationBtn = document.getElementById('copyCitationBtn');

  if (citationFormatSelect && citationTextEl) {
    const citations = {
      apa: `Yauri, N. M., & Mohammed, S. D. (2026). Digital Tax Administration, Compliance Costs, and Revenue Mobilization in Emerging African Economies. African Journal of Finance, Taxation, and Fiscal Policy, 1(1), 12–29. https://doi.org/10.5281/ajftfp.2026.0101`,
      harvard: `Yauri, N.M. and Mohammed, S.D., 2026. Digital Tax Administration, Compliance Costs, and Revenue Mobilization in Emerging African Economies. African Journal of Finance, Taxation, and Fiscal Policy, 1(1), pp.12–29.`,
      chicago: `Yauri, Nasiru Musa, and Sani Damamisau Mohammed. "Digital Tax Administration, Compliance Costs, and Revenue Mobilization in Emerging African Economies." African Journal of Finance, Taxation, and Fiscal Policy 1, no. 1 (2026): 12–29.`,
      mla: `Yauri, Nasiru Musa, and Sani Damamisau Mohammed. "Digital Tax Administration, Compliance Costs, and Revenue Mobilization in Emerging African Economies." African Journal of Finance, Taxation, and Fiscal Policy, vol. 1, no. 1, 2026, pp. 12–29.`,
      bibtex: `@article{yauri2026digital,
  title={Digital Tax Administration, Compliance Costs, and Revenue Mobilization in Emerging African Economies},
  author={Yauri, Nasiru Musa and Mohammed, Sani Damamisau},
  journal={African Journal of Finance, Taxation, and Fiscal Policy},
  volume={1},
  number={1},
  pages={12--29},
  year={2026},
  publisher={Department of Management Sciences, Federal University Dutse},
  doi={10.5281/ajftfp.2026.0101}
}`
    };

    const updateCitation = () => {
      const selected = citationFormatSelect.value;
      if (citations[selected]) {
        citationTextEl.textContent = citations[selected];
      }
    };

    citationFormatSelect.addEventListener('change', updateCitation);

    if (copyCitationBtn) {
      copyCitationBtn.addEventListener('click', () => {
        const text = citationTextEl.textContent;
        navigator.clipboard.writeText(text).then(() => {
          const originalHTML = copyCitationBtn.innerHTML;
          copyCitationBtn.innerHTML = '<i class="bi bi-check2"></i> Copied!';
          copyCitationBtn.classList.remove('btn-outline-primary', 'btn-navy');
          copyCitationBtn.classList.add('btn-success');
          setTimeout(() => {
            copyCitationBtn.innerHTML = originalHTML;
            copyCitationBtn.classList.remove('btn-success');
            copyCitationBtn.classList.add('btn-navy');
          }, 2000);
        });
      });
    }
  }

  // -------------------------------------------------------------
  // 2. Live Search & Category Filtering for Archive / Current Issue
  // -------------------------------------------------------------
  const searchInput = document.getElementById('articleSearchInput');
  const categoryFilter = document.getElementById('categoryFilter');
  const yearFilter = document.getElementById('yearFilter');
  const articleCards = document.querySelectorAll('.filterable-article');
  const noResultsEl = document.getElementById('noResultsMsg');
  const resultsCountEl = document.getElementById('resultsCount');

  const filterArticles = () => {
    if (!articleCards.length) return;

    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const selectedCategory = categoryFilter ? categoryFilter.value.toLowerCase() : 'all';
    const selectedYear = yearFilter ? yearFilter.value : 'all';

    let matchCount = 0;

    articleCards.forEach(card => {
      const title = card.getAttribute('data-title')?.toLowerCase() || '';
      const authors = card.getAttribute('data-authors')?.toLowerCase() || '';
      const category = card.getAttribute('data-category')?.toLowerCase() || '';
      const year = card.getAttribute('data-year') || '';
      const textContent = card.textContent.toLowerCase();

      const matchesSearch = !searchTerm || title.includes(searchTerm) || authors.includes(searchTerm) || textContent.includes(searchTerm);
      const matchesCategory = selectedCategory === 'all' || category.includes(selectedCategory);
      const matchesYear = selectedYear === 'all' || year === selectedYear;

      if (matchesSearch && matchesCategory && matchesYear) {
        card.style.display = 'block';
        matchCount++;
      } else {
        card.style.display = 'none';
      }
    });

    if (resultsCountEl) {
      resultsCountEl.textContent = `${matchCount} article${matchCount === 1 ? '' : 's'} found`;
    }

    if (noResultsEl) {
      noResultsEl.style.display = matchCount === 0 ? 'block' : 'none';
    }
  };

  if (searchInput) searchInput.addEventListener('input', filterArticles);
  if (categoryFilter) categoryFilter.addEventListener('change', filterArticles);
  if (yearFilter) yearFilter.addEventListener('change', filterArticles);

  // -------------------------------------------------------------
  // 3. Online Submission Portal Form Handler
  // -------------------------------------------------------------
  const submissionForm = document.getElementById('manuscriptSubmissionForm');
  if (submissionForm) {
    submissionForm.addEventListener('submit', (e) => {
      e.preventDefault();
      
      const checkOrig = document.getElementById('checkOrig') || document.getElementById('agreeEthics');
      if (checkOrig && !checkOrig.checked) {
        alert('Please review and confirm the originality declaration and Turnitin < 15% limit.');
        checkOrig.focus();
        return;
      }

      // Check file inputs
      const manuscriptFile = document.getElementById('manuscriptFile');
      if (manuscriptFile && !manuscriptFile.files.length) {
        alert('Please upload your blinded manuscript document (.doc or .docx).');
        manuscriptFile.focus();
        return;
      }

      const submitBtn = submissionForm.querySelector('button[type="submit"]');
      const originalText = submitBtn ? submitBtn.innerHTML : 'Submit Manuscript';
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting Manuscript...';
      }

      setTimeout(() => {
        const modalEl = document.getElementById('submissionSuccessModal');
        if (modalEl) {
          const successModal = new bootstrap.Modal(modalEl);
          successModal.show();
        } else {
          alert('Manuscript Submitted Successfully! Your tracking reference will be sent to your email.');
        }
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        }
        submissionForm.reset();
      }, 1200);
    });
  }

  // -------------------------------------------------------------
  // 4. Contact Form Handler
  // -------------------------------------------------------------
  const contactForm = document.getElementById('contactInquiryForm') || document.getElementById('journalContactForm');
  if (contactForm) {
    contactForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const submitBtn = contactForm.querySelector('button[type="submit"]');
      const originalText = submitBtn ? submitBtn.innerHTML : 'Send Inquiry';
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending...';
      }

      setTimeout(() => {
        alert('Thank you for contacting the AJFTFP Editorial Office. We will get back to you within 24–48 business hours.');
        contactForm.reset();
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        }
      }, 1000);
    });
  }
});
