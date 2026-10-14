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
  publisher={Department of Taxation, Federal University Dutse},
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

      // Check file inputs
      const manuscriptFile = document.getElementById('manuscriptFile');
      if (manuscriptFile && !manuscriptFile.files.length) {
        alert('Please upload your manuscript document (.doc, .docx, or .pdf).');
        manuscriptFile.focus();
        return;
      }

      const submitBtn = submissionForm.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting Manuscript...';

      const formData = new FormData(submissionForm);

      fetch('admin/submit_api.php', {
        method: 'POST',
        body: formData
      })
      .then(async response => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;

        if (!response.ok) {
          const errorText = await response.text();
          throw new Error(`Server returned HTTP ${response.status}: ${errorText.substring(0, 150)}`);
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          const modalEl = document.getElementById('submissionSuccessModal');
          if (modalEl) {
            // Update tracking ref badge in modal
            const trackingBadge = modalEl.querySelector('.badge.bg-primary');
            if (trackingBadge && data.tracking_ref) {
              trackingBadge.textContent = data.tracking_ref;
            }
            const successModal = new bootstrap.Modal(modalEl);
            successModal.show();
          } else {
            alert('Manuscript Submitted Successfully! Reference: ' + (data.tracking_ref || ''));
          }
          submissionForm.reset();
        } else {
          alert('Submission Failed: ' + (data.message || 'Unable to process manuscript submission.'));
        }
      })
      .catch(err => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        console.error('Submission error:', err);
        alert('Submission failed: ' + (err.message || 'Network error connecting to backend.'));
      });
    });
  }

  // -------------------------------------------------------------
  // 4. Contact Form Handler
  // -------------------------------------------------------------
  const contactForm = document.getElementById('journalContactForm');
  if (contactForm) {
    contactForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const submitBtn = contactForm.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending...';

      setTimeout(() => {
        alert('Thank you for contacting AJFTFP Editorial Office. We will get back to you within 24–48 business hours.');
        contactForm.reset();
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
      }, 1200);
    });
  }

  // -------------------------------------------------------------
  // 4b. Manuscript Tracking Handler
  // -------------------------------------------------------------
  const trackForm = document.getElementById('trackManuscriptForm');
  const trackRefInput = document.getElementById('trackRefInput');
  const trackResultBox = document.getElementById('trackResultBox');

  if (trackForm && trackRefInput && trackResultBox) {
    trackForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const ref = trackRefInput.value.trim();
      if (!ref) return;

      const submitBtn = trackForm.querySelector('button[type="submit"]');
      const origText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

      fetch(`api_articles.php?action=track&ref=${encodeURIComponent(ref)}`)
        .then(res => res.json())
        .then(data => {
          submitBtn.disabled = false;
          submitBtn.innerHTML = origText;
          trackResultBox.style.display = 'block';

          if (data.success && data.data) {
            const d = data.data;
            const statusClass = d.status === 'Accepted' ? 'text-success' : (d.status === 'Under Review' ? 'text-warning' : 'text-primary');
            trackResultBox.innerHTML = `
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="badge bg-navy text-white">${d.tracking_ref}</span>
                <span class="fw-bold ${statusClass}"><i class="bi bi-clock-history me-1"></i>${d.status}</span>
              </div>
              <div class="fw-bold text-dark mb-1" style="line-height: 1.3;">${d.manuscript_title}</div>
              <div class="text-muted small mb-1">Author: ${d.author} &bull; Submitted: ${d.date}</div>
              ${d.editorial_notes ? `<div class="p-2 mt-2 bg-white border rounded"><strong>Editor Note:</strong> <span class="text-muted">${d.editorial_notes}</span></div>` : ''}
            `;
          } else {
            trackResultBox.innerHTML = `
              <div class="text-danger fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Reference Not Found</div>
              <p class="text-muted mb-0 small">${data.message || 'Please check your tracking code and try again.'}</p>
            `;
          }
        })
        .catch(err => {
          submitBtn.disabled = false;
          submitBtn.innerHTML = origText;
          trackResultBox.style.display = 'block';
          trackResultBox.innerHTML = `<div class="text-danger small">Network error checking status. Please try again.</div>`;
        });
    });
  }

  // -------------------------------------------------------------
  // 5. Dynamic Published Articles Loader (Current Issue, Archives, Home, Detail)
  // -------------------------------------------------------------
  const homeContainer = document.getElementById('dynamicPublishedHomeContainer');
  const currentIssueContainer = document.getElementById('publishedArticlesContainer');
  const archivesContainer = document.getElementById('publishedArchivesContainer');
  const articleDetailCol = document.getElementById('articleDetailMainCol');

  // Helper to build a clean scholarly article card
  const createArticleCardHTML = (art) => {
    const docxBtn = art.file_docx 
      ? `<a href="${art.file_docx}" download class="btn btn-sm btn-outline-primary py-1 px-2" title="Download Word"><i class="bi bi-file-earmark-word me-1"></i>DOCX</a>` 
      : '';
    const pdfBtn = art.file_pdf 
      ? `<a href="${art.file_pdf}" download class="btn btn-sm btn-outline-danger py-1 px-2" title="Download PDF"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>` 
      : '';

    return `
      <div class="academic-card p-4 mb-3 filterable-article" 
           data-title="${art.title ? art.title.replace(/"/g, '&quot;') : ''}" 
           data-authors="${art.authors ? art.authors.replace(/"/g, '&quot;') : ''}" 
           data-category="${art.category ? art.category.toLowerCase().replace(/"/g, '&quot;') : ''}" 
           data-year="${art.year || ''}">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
          <span class="badge bg-light text-navy border fw-bold">Vol. ${art.volume} No. ${art.issue} (${art.year})</span>
          <span class="badge-oa"><i class="bi bi-unlock-fill"></i> Gold Open Access (CC BY 4.0)</span>
        </div>
        <h5 class="font-serif mb-2">
          <a href="article-detail.html?id=${art.id}" class="text-navy text-decoration-none hover-gold">
            ${art.title}
          </a>
        </h5>
        <div class="small text-muted mb-2">
          <i class="bi bi-person me-1"></i> <strong>${art.authors}</strong> 
          ${art.affiliations ? `&bull; <em>${art.affiliations}</em>` : ''}
        </div>
        <div class="small text-muted mb-2">
          <i class="bi bi-link-45deg me-1"></i> <strong>DOI:</strong> <span class="text-dark font-monospace">${art.doi}</span>
        </div>
        <p class="small text-muted mb-3" style="text-align: justify; line-height: 1.6;">
          ${art.abstract.length > 250 ? art.abstract.substring(0, 250) + '...' : art.abstract}
        </p>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-2 border-top">
          <span class="badge bg-light text-dark border">${art.category || 'Research Article'}</span>
          <div class="d-flex gap-2">
            <a href="article-detail.html?id=${art.id}" class="btn btn-sm btn-gold fw-bold py-1 px-2">
              <i class="bi bi-book-half me-1"></i> Read Article
            </a>
            ${pdfBtn}
            ${docxBtn}
          </div>
        </div>
      </div>
    `;
  };

  // If on Single Article Detail page and an ?id= parameter is present
  if (articleDetailCol) {
    const urlParams = new URLSearchParams(window.location.search);
    const articleId = urlParams.get('id');

    if (articleId) {
      fetch(`api_articles.php?action=detail&id=${articleId}`)
        .then(res => res.json())
        .then(data => {
          if (data.success && data.article) {
            const art = data.article;
            document.title = `${art.title} | AJFTFP`;

            const docxDownload = art.file_docx 
              ? `<a href="${art.file_docx}" download class="btn btn-outline-primary btn-sm"><i class="bi bi-file-earmark-word me-1"></i> Download Word (.docx)</a>` 
              : '';
            const pdfDownload = art.file_pdf 
              ? `<a href="${art.file_pdf}" download class="btn btn-danger btn-sm fw-bold"><i class="bi bi-file-earmark-pdf me-1"></i> Download PDF</a>` 
              : '';

            articleDetailCol.innerHTML = `
              <article class="academic-card p-4 mb-3" style="border-left: 4px solid var(--aj-gold-accent) !important;">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                  <span class="badge bg-navy text-white fw-bold">Vol. ${art.volume} No. ${art.issue} (${art.year})</span>
                  <span class="badge-oa"><i class="bi bi-unlock-fill"></i> Gold Open Access (CC BY 4.0)</span>
                </div>

                <h2 class="font-serif text-navy mb-2">${art.title}</h2>

                <div class="text-muted small mb-2">
                  <i class="bi bi-person me-1"></i> <strong>Authors:</strong> ${art.authors}
                </div>
                <div class="text-muted small mb-2">
                  <i class="bi bi-building me-1"></i> <strong>Affiliation:</strong> ${art.affiliations || 'Department of Management Sciences, Federal University Dutse'}
                </div>
                <div class="text-muted small mb-3">
                  <i class="bi bi-link-45deg me-1"></i> <strong>DOI:</strong> <a href="https://doi.org/${art.doi}" target="_blank" class="font-monospace text-primary">${art.doi}</a>
                  &bull; <strong>Published:</strong> ${art.published_date || ''}
                </div>

                <div class="p-3 bg-light border rounded mb-3">
                  <h6 class="fw-bold text-navy mb-2"><i class="bi bi-file-earmark-text text-primary me-1"></i> Abstract</h6>
                  <p class="small text-muted mb-0" style="text-align: justify; line-height: 1.8;">
                    ${art.abstract.replace(/\n/g, '<br>')}
                  </p>
                </div>

                ${art.keywords ? `
                  <div class="mb-3 small">
                    <strong>Keywords:</strong> <span class="text-muted">${art.keywords}</span>
                  </div>
                ` : ''}

                <div class="d-flex flex-wrap gap-2 pt-3 border-top">
                  ${pdfDownload}
                  ${docxDownload}
                  <a href="current-issue.html" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Return to Issue
                  </a>
                </div>
              </article>
            `;
          }
        })
        .catch(err => console.error('Error fetching article details:', err));
    }
  }

  // Load articles for Current Issue, Archives, and Homepage
  if (homeContainer || currentIssueContainer || archivesContainer) {
    fetch('api_articles.php')
      .then(res => res.json())
      .then(data => {
        if (data.success && data.articles && data.articles.length > 0) {
          const articles = data.articles;

          // 1. Current Issue Page
          if (currentIssueContainer) {
            let html = `
              <div class="academic-card p-4 mb-3">
                <div class="section-title-wrap mb-3">
                  <div class="sub-heading">Table of Contents</div>
                  <h4 class="mb-0 font-serif">Articles Published in this Issue</h4>
                  <div class="section-title-divider"></div>
                </div>
                <div>
                  ${articles.map(createArticleCardHTML).join('')}
                </div>
              </div>
            `;
            currentIssueContainer.innerHTML = html;
          }

          // 2. Archives Page
          if (archivesContainer) {
            let html = `
              <div class="academic-card p-4 mb-3">
                <div class="section-title-wrap mb-3">
                  <div class="sub-heading">Journal Catalog</div>
                  <h4 class="mb-0 font-serif">Published Volumes &amp; Articles (${articles.length})</h4>
                  <div class="section-title-divider"></div>
                </div>
                <div>
                  ${articles.map(createArticleCardHTML).join('')}
                </div>
              </div>
            `;
            archivesContainer.innerHTML = html;

            const resultsCountEl = document.getElementById('resultsCount');
            if (resultsCountEl) {
              resultsCountEl.textContent = `${articles.length} article${articles.length === 1 ? '' : 's'} cataloged`;
            }
          }

          // 3. Homepage (Latest Published Articles)
          if (homeContainer) {
            let html = `
              <section class="academic-card p-4 mb-3">
                <div class="section-title-wrap mb-3">
                  <div class="sub-heading">Latest Publications</div>
                  <h3 class="mb-0 font-serif">Recent Scholarly Articles</h3>
                  <div class="section-title-divider"></div>
                </div>
                <div>
                  ${articles.slice(0, 5).map(createArticleCardHTML).join('')}
                </div>
                <div class="text-center pt-2">
                  <a href="current-issue.html" class="btn btn-outline-navy btn-sm fw-bold">
                    View Complete Current Issue <i class="bi bi-arrow-right ms-1"></i>
                  </a>
                </div>
              </section>
            `;
            homeContainer.innerHTML = html;
          }
        }
      })
      .catch(err => {
        console.warn('api_articles.php not reachable or no database found; keeping static fallback.', err);
      });
  }
});
