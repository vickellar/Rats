// Add this JavaScript function to your existing calculator file

<script>
// Enhanced export function with paper size selection
function exportToPDF() {
    // Show paper size selection modal
    showPaperSizeModal();
}

function showPaperSizeModal() {
    // Create modal HTML
    const modalHTML = `
        <div id="paperSizeModal" style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        ">
            <div style="
                background: white;
                padding: 30px;
                border-radius: 12px;
                max-width: 500px;
                width: 90%;
                box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            ">
                <h3 style="margin-bottom: 20px; text-align: center; color: #333;">
                    <i class="fas fa-file-pdf"></i> Select PDF Paper Size
                </h3>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: flex; align-items: center; padding: 10px; border: 2px solid #e0e0e0; border-radius: 6px; margin-bottom: 10px; cursor: pointer;" onclick="selectPaperSize('A4')">
                        <input type="radio" name="paperSize" value="A4" checked style="margin-right: 10px;">
                        <div>
                            <strong>A4</strong> - 210 × 297 mm<br>
                            <small style="color: #666;">Standard international size</small>
                        </div>
                    </label>
                    
                    <label style="display: flex; align-items: center; padding: 10px; border: 2px solid #e0e0e0; border-radius: 6px; margin-bottom: 10px; cursor: pointer;" onclick="selectPaperSize('A3')">
                        <input type="radio" name="paperSize" value="A3" style="margin-right: 10px;">
                        <div>
                            <strong>A3</strong> - 297 × 420 mm<br>
                            <small style="color: #666;">Large format</small>
                        </div>
                    </label>
                    
                    <label style="display: flex; align-items: center; padding: 10px; border: 2px solid #e0e0e0; border-radius: 6px; margin-bottom: 10px; cursor: pointer;" onclick="selectPaperSize('A5')">
                        <input type="radio" name="paperSize" value="A5" style="margin-right: 10px;">
                        <div>
                            <strong>A5</strong> - 148 × 210 mm<br>
                            <small style="color: #666;">Compact size</small>
                        </div>
                    </label>
                    
                    <label style="display: flex; align-items: center; padding: 10px; border: 2px solid #e0e0e0; border-radius: 6px; margin-bottom: 10px; cursor: pointer;" onclick="selectPaperSize('Letter')">
                        <input type="radio" name="paperSize" value="Letter" style="margin-right: 10px;">
                        <div>
                            <strong>Letter</strong> - 216 × 279 mm<br>
                            <small style="color: #666;">US standard</small>
                        </div>
                    </label>
                    
                    <label style="display: flex; align-items: center; padding: 10px; border: 2px solid #e0e0e0; border-radius: 6px; margin-bottom: 10px; cursor: pointer;" onclick="selectPaperSize('Legal')">
                        <input type="radio" name="paperSize" value="Legal" style="margin-right: 10px;">
                        <div>
                            <strong>Legal</strong> - 216 × 356 mm<br>
                            <small style="color: #666;">US legal size</small>
                        </div>
                    </label>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button onclick="closePaperSizeModal()" style="
                        flex: 1;
                        padding: 12px;
                        border: 2px solid #ddd;
                        background: white;
                        border-radius: 6px;
                        cursor: pointer;
                    ">Cancel</button>
                    <button onclick="generatePDFWithSize()" style="
                        flex: 1;
                        padding: 12px;
                        border: none;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        border-radius: 6px;
                        cursor: pointer;
                    ">
                        <i class="fas fa-download"></i> Generate PDF
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

function selectPaperSize(size) {
    // Update radio button selection
    const radios = document.querySelectorAll('input[name="paperSize"]');
    radios.forEach(radio => {
        radio.checked = radio.value === size;
    });
    
    // Update visual selection
    const labels = document.querySelectorAll('#paperSizeModal label');
    labels.forEach(label => {
        label.style.borderColor = '#e0e0e0';
        label.style.background = 'white';
    });
    
    event.currentTarget.style.borderColor = '#667eea';
    event.currentTarget.style.background = '#f8f9ff';
}

function closePaperSizeModal() {
    const modal = document.getElementById('paperSizeModal');
    if (modal) {
        modal.remove();
    }
}

function generatePDFWithSize() {
    const selectedSize = document.querySelector('input[name="paperSize"]:checked').value;
    
    // Collect form data
    const formData = collectFormData();
    
    if (!formData.isValid) {
        alert('Please fill in all required fields before exporting to PDF.');
        return;
    }

    // Create a form to submit to PDF generation script
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'generate_pdf_optimized.php';
    form.target = '_blank';

    // Add paper size
    const paperSizeInput = document.createElement('input');
    paperSizeInput.type = 'hidden';
    paperSizeInput.name = 'paper_size';
    paperSizeInput.value = selectedSize;
    form.appendChild(paperSizeInput);

    // Add form data as hidden inputs
    Object.keys(formData.data).forEach(key => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = formData.data[key];
        form.appendChild(input);
    });

    // Add property and application IDs
    const propertyInput = document.createElement('input');
    propertyInput.type = 'hidden';
    propertyInput.name = 'property_id';
    propertyInput.value = '<?php echo htmlspecialchars($propertyId ?? ''); ?>';
    form.appendChild(propertyInput);

    const applicationInput = document.createElement('input');
    applicationInput.type = 'hidden';
    applicationInput.name = 'application_id';
    applicationInput.value = '<?php echo htmlspecialchars($applicationId ?? ''); ?>';
    form.appendChild(applicationInput);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    // Close modal
    closePaperSizeModal();
}
</script>