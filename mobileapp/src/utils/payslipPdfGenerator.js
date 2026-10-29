export function generatePayslipHtml(payslip) {
  const emp = payslip.employee || {};
  const comp = payslip.company || {};
  const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ];
  const monthName = monthNames[(payslip.month || 1) - 1] || 'Month';
  const cycle = `${monthName} ${payslip.year}`;

  const fmt = (num) => '₹' + parseFloat(num || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  return `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Payslip - ${emp.full_name || 'Employee'}</title>
      <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 24px; color: #1e293b; background: #fff; }
        .header { border-bottom: 2px solid #0284c7; padding-bottom: 16px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-start; }
        .company-title { font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 4px; }
        .company-sub { font-size: 11px; color: #64748b; }
        .payslip-badge { background: #e0f2fe; color: #0369a1; padding: 6px 12px; border-radius: 8px; font-weight: 800; font-size: 12px; text-transform: uppercase; }
        .grid-2 { display: flex; justify-content: space-between; gap: 20px; margin-bottom: 20px; }
        .info-card { flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 12px; }
        .info-label { color: #64748b; font-weight: 600; }
        .info-val { color: #0f172a; font-weight: 700; text-align: right; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th { background: #f1f5f9; padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; color: #475569; border-bottom: 1px solid #cbd5e1; }
        .table td { padding: 10px 12px; font-size: 12px; border-bottom: 1px solid #e2e8f0; }
        .text-right { text-align: right; }
        .summary-box { background: #0f172a; color: #fff; border-radius: 12px; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .summary-label { font-size: 13px; font-weight: 600; color: #94a3b8; }
        .summary-val { font-size: 22px; font-weight: 800; color: #38bdf8; }
        .footer { text-align: center; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 30px; }
      </style>
    </head>
    <body>
      <div class="header">
        <div>
          <div class="company-title">${comp.name || 'HRMS Enterprise India'}</div>
          <div class="company-sub">Official Salary Disbursement Advice</div>
          <div class="company-sub">Payslip Reference: <b>${payslip.payslip_number || 'N/A'}</b></div>
        </div>
        <div class="payslip-badge">Pay Period: ${cycle}</div>
      </div>

      <div class="grid-2">
        <div class="info-card">
          <div class="info-row"><span class="info-label">Employee Name:</span><span class="info-val">${emp.full_name || 'N/A'}</span></div>
          <div class="info-row"><span class="info-label">Employee ID:</span><span class="info-val">${emp.employee_id || 'N/A'}</span></div>
          <div class="info-row"><span class="info-label">Department:</span><span class="info-val">${emp.department?.name || 'N/A'}</span></div>
          <div class="info-row"><span class="info-label">Designation:</span><span class="info-val">${emp.designation?.name || 'N/A'}</span></div>
        </div>
        <div class="info-card">
          <div class="info-row"><span class="info-label">Joining Date:</span><span class="info-val">${emp.joining_date || 'N/A'}</span></div>
          <div class="info-row"><span class="info-label">Bank Account:</span><span class="info-val">${emp.bank_account_number ? '••••' + emp.bank_account_number.slice(-4) : 'Direct Transfer'}</span></div>
          <div class="info-row"><span class="info-label">PAN Number:</span><span class="info-val">${emp.pan_number || 'N/A'}</span></div>
          <div class="info-row"><span class="info-label">UAN / PF:</span><span class="info-val">${emp.uan_number || 'N/A'}</span></div>
        </div>
      </div>

      <div class="grid-2">
        <div style="flex: 1;">
          <table class="table">
            <thead>
              <tr><th>Earnings & Allowances</th><th class="text-right">Amount</th></tr>
            </thead>
            <tbody>
              <tr><td>Basic Salary</td><td class="text-right font-bold">${fmt(payslip.basic_salary)}</td></tr>
              <tr><td>Dearness Allowance (DA)</td><td class="text-right">${fmt(payslip.da_amount)}</td></tr>
              <tr><td>House Rent Allowance (HRA)</td><td class="text-right">${fmt(payslip.hra_amount)}</td></tr>
              <tr><td>Conveyance Allowance</td><td class="text-right">${fmt(payslip.conveyance_amount)}</td></tr>
              <tr><td>Entertainment Allowance</td><td class="text-right">${fmt(payslip.entertainment_amount)}</td></tr>
              <tr><td>Special / Other Allowance</td><td class="text-right">${fmt(payslip.special_allowance_amount)}</td></tr>
              <tr style="background:#f8fafc; font-weight:bold;"><td>Total Gross Earnings</td><td class="text-right">${fmt(payslip.gross_salary)}</td></tr>
            </tbody>
          </table>
        </div>
        <div style="flex: 1;">
          <table class="table">
            <thead>
              <tr><th>Statutory & Deductions</th><th class="text-right">Amount</th></tr>
            </thead>
            <tbody>
              <tr><td>Provident Fund (Employee PF)</td><td class="text-right">${fmt(payslip.employee_pf_amount)}</td></tr>
              <tr><td>ESIC Contribution</td><td class="text-right">${fmt(payslip.employee_esic_amount)}</td></tr>
              <tr><td>TDS / Income Tax</td><td class="text-right">${fmt(payslip.tax_deduction_amount)}</td></tr>
              <tr><td>Leave / LOP Deductions (${payslip.total_deductible_days || 0} days)</td><td class="text-right" style="color:#ef4444;">${fmt(payslip.leave_deduction_amount)}</td></tr>
              <tr><td>Other Deductions</td><td class="text-right">${fmt(payslip.other_deduction_amount)}</td></tr>
              <tr style="background:#f8fafc; font-weight:bold;"><td>Total Deductions</td><td class="text-right">${fmt(payslip.total_deductions)}</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="summary-box">
        <div>
          <div class="summary-label">NET TAKE-HOME SALARY DISBURSED</div>
          <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Deposited into employee bank account via NEFT/RTGS</div>
        </div>
        <div class="summary-val">${fmt(payslip.net_salary)}</div>
      </div>

      <div class="footer">
        <p>This is a computer-generated salary slip and does not require a physical signature.</p>
        <p>Confidential • HRMS Enterprise Cloud Workforce Management</p>
      </div>
    </body>
    </html>
  `;
}
