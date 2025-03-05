async function generatePDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // Fetch form data
    const date = document.getElementById('certificateDate').value;
    const registeredOwner = document.getElementById('registeredOwner').value;
    const propertyAddress = document.getElementById('propertyAddress').value;
    const certificateExpiry = document.getElementById('certificateExpiry').value;
    const applicantAddress = document.getElementById('applicantAddress').value;
    const agentAddress = document.getElementById('agentAddress').value;
    const financeDirector = document.getElementById('financeDirector').value;

    // Add form data to PDF
    doc.setFontSize(12);
    doc.text(`Date: ${date}`, 20, 80);
    doc.text(`Registered Owner: ${registeredOwner}`, 20, 90);
    doc.text(`Property Address: ${propertyAddress}`, 20, 100);
    doc.text(`Certificate Expiry Date: ${certificateExpiry}`, 20, 110);
    doc.text(`Applicant Address: ${applicantAddress}`, 20, 120);
    doc.text(`Agent's Address: ${agentAddress}`, 20, 130);
    doc.text(`Finance Director Signature: ${financeDirector}`, 20, 140);

    // Generate QR Code
    const qrCodeData = `Owner: ${registeredOwner}\nAddress: ${propertyAddress}\nExpiry: ${certificateExpiry}`;
    const qrCodeCanvas = document.createElement('canvas');
    
    $(qrCodeCanvas).qrcode({ width: 100, height: 100, text: qrCodeData });
    
    // Convert QR Code to image data
    const qrCodeImageData = qrCodeCanvas.toDataURL('image/png');

    // Add QR Code to PDF
    doc.addImage(qrCodeImageData, 'PNG', 150, 60, 40, 40); // Adjust position and size as needed

    // Save the PDF
    doc.save('certificate_application.pdf');
}