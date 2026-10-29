<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Slip - {{ $payslip->employee->full_name }} ({{ date('M/Y', mktime(0, 0, 0, $payslip->month, 1, $payslip->year)) }})</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            background-color: #f1f5f9;
            padding: 20px;
            font-size: 11px;
            line-height: 1.35;
        }

        .no-print {
            max-width: 800px;
            margin: 0 auto 15px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .no-print a {
            color: #334155;
            text-decoration: none;
            font-weight: bold;
            font-size: 12px;
        }

        .no-print button {
            background-color: #0f172a;
            color: #ffffff;
            border: none;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
        }

        .no-print button:hover {
            background-color: #1e293b;
        }

        .payslip-page {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px 40px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* HEADER SECTION */
        .company-header {
            text-align: center;
            margin-bottom: 18px;
        }

        .company-name {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 0.3px;
            margin-bottom: 3px;
        }

        .company-address {
            font-size: 11px;
            font-weight: normal;
            margin-bottom: 2px;
            color: #111;
        }

        .company-cin {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .payslip-title {
            font-size: 12px;
            font-weight: bold;
            margin-top: 4px;
        }

        /* 4-COLUMN INFO GRID */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .info-table td {
            padding: 2.5px 4px;
            vertical-align: top;
            font-size: 10.5px;
        }

        .info-label {
            font-weight: normal;
            width: 18%;
            white-space: nowrap;
        }

        .info-colon {
            width: 2%;
            text-align: center;
        }

        .info-val {
            font-weight: normal;
            width: 30%;
        }

        /* EARNINGS & DEDUCTIONS TABLE */
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 0px;
        }

        .salary-table th {
            border: 1px solid #000;
            padding: 5px 8px;
            font-size: 11px;
            font-weight: bold;
            background-color: #ffffff;
        }

        .salary-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 3.5px 8px;
            font-size: 10.5px;
            vertical-align: top;
        }

        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .salary-spacer-row td {
            height: 180px; /* Spacer to give traditional payslip height as in reference */
        }

        .salary-totals-row td {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 5px 8px;
            font-weight: bold;
            font-size: 11px;
        }

        /* NET PAY & IN WORDS BOX */
        .net-pay-section {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 6px 8px;
        }

        .net-pay-row {
            display: flex;
            align-items: baseline;
            margin-bottom: 4px;
        }

        .net-pay-label {
            font-weight: bold;
            font-size: 11.5px;
            width: 90px;
        }

        .net-pay-amount {
            font-weight: bold;
            font-size: 12px;
        }

        .in-words-row {
            display: flex;
            align-items: baseline;
        }

        .in-words-label {
            font-weight: bold;
            font-size: 11px;
            width: 90px;
        }

        .in-words-val {
            font-size: 11px;
            font-weight: bold;
        }

        /* SIGNATURE */
        .signature-section {
            text-align: right;
            margin-top: 25px;
            margin-bottom: 15px;
            padding-right: 20px;
        }

        .signature-title {
            font-weight: bold;
            font-size: 11px;
        }

        /* BOTTOM REIMBURSEMENT / ADVANCE STRIP */
        .advance-strip {
            width: 100%;
            border-top: 1px dashed #000;
            padding-top: 8px;
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }

        .mini-table {
            border-collapse: collapse;
            border: 1px solid #000;
            font-size: 10px;
            flex: 1;
        }

        .mini-table th, .mini-table td {
            border: 1px solid #000;
            padding: 3px 5px;
            text-align: center;
        }

        .mini-table th {
            font-weight: bold;
        }

        /* SYSTEM FOOTER */
        .system-footer {
            text-align: center;
            margin-top: 35px;
            font-size: 10px;
            color: #000;
        }

        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .payslip-page {
                box-shadow: none;
                padding: 20px 25px;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    @php
        $emp = $payslip->employee;
        $comp = $emp->company ?: $payslip->company;
        
        // Month name & Year (auto fetched from payslip)
        $monthName = date('M', mktime(0, 0, 0, $payslip->month, 1));
        $payMonthFormatted = $monthName . '/' . $payslip->year;

        // Number to Indian Words Helper
        function numberToIndianWords($num) {
            $num = (int) round($num);
            if ($num == 0) return 'Zero';

            $words = [
                0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
                5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
                10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
                15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen',
                20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty',
                60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
            ];

            $digits = ['', 'Hundred', 'Thousand', 'Lakh', 'Crore'];
            $res = '';

            $crore = floor($num / 10000000);
            $num = $num % 10000000;

            $lakh = floor($num / 100000);
            $num = $num % 100000;

            $thousand = floor($num / 1000);
            $num = $num % 1000;

            $hundred = floor($num / 100);
            $num = $num % 100;

            if ($crore > 0) {
                $res .= ($crore < 20 ? $words[$crore] : $words[floor($crore/10)*10] . ' ' . $words[$crore%10]) . ' Crore ';
            }
            if ($lakh > 0) {
                $res .= ($lakh < 20 ? $words[$lakh] : $words[floor($lakh/10)*10] . ' ' . $words[$lakh%10]) . ' Lakh ';
            }
            if ($thousand > 0) {
                $res .= ($thousand < 20 ? $words[$thousand] : $words[floor($thousand/10)*10] . ' ' . $words[$thousand%10]) . ' Thousand ';
            }
            if ($hundred > 0) {
                $res .= $words[$hundred] . ' Hundred ';
            }
            if ($num > 0) {
                $res .= ($num < 20 ? $words[$num] : $words[floor($num/10)*10] . ' ' . $words[$num%10]) . ' ';
            }

            return trim($res);
        }

        $inWords = 'Rupees ' . numberToIndianWords($payslip->net_salary) . ' Only';

        // Calculation of actual vs theoretical amounts
        // In our system: Theoretical is the standard gross components
        // Actual reflects any leave deduction proportional adjustment or direct leave deduction line
        $theoreticalGross = $payslip->gross_salary;
        $actualGross = max(0, $theoreticalGross - $payslip->leave_deduction_amount);
        $totalDeductions = $payslip->statutory_deductions;
    @endphp

    <!-- Top Action Toolbar -->
    <div class="no-print">
        <a href="{{ route('payroll.index') }}">&larr; Back to Payroll</a>
        <button onclick="window.print()">Print / Save PDF</button>
    </div>

    <!-- Official Clean White Payslip Container -->
    <div class="payslip-page">

        <!-- HEADER SECTION -->
        <div class="company-header">
            <div class="company-name">{{ $comp?->name ?: 'Riangdo Veneers Pvt. Ltd.' }}</div>
            <div class="company-address">
                Corporate Office: {{ $comp?->address ?: 'Industrial Growth Centre, Balipara' }}, 
                {{ $comp?->city ?: 'Guwahati' }}, {{ $comp?->state ?: 'Assam' }} - {{ $comp?->pincode ?: '781001' }}
            </div>
            <div class="company-cin">
                CIN : {{ $comp?->cin ?: 'U20211AS2018PTC018742' }}
            </div>
            <div class="payslip-title">
                Pay Slip for the month of {{ $payMonthFormatted }}
            </div>
        </div>

        <!-- 4-COLUMN EMPLOYEE INFORMATION DETAILS -->
        <table class="info-table">
            <tr>
                <td class="info-label">Emp ID</td>
                <td class="info-colon">:</td>
                <td class="info-val"><strong>{{ $emp->employee_id }}</strong></td>

                <td class="info-label">Employee Name</td>
                <td class="info-colon">:</td>
                <td class="info-val"><strong>{{ strtoupper($emp->full_name) }}</strong></td>
            </tr>
            <tr>
                <td class="info-label">PF No.</td>
                <td class="info-colon">:</td>
                <td class="info-val">{{ $emp->uan_number ?: 'AP/GHY/004521/001' }}</td>

                <td class="info-label">ESI No.</td>
                <td class="info-colon">:</td>
                <td class="info-val">{{ $emp->esic_number ?: '13000458920001001' }}</td>
            </tr>
            <tr>
                <td class="info-label">Pay Days</td>
                <td class="info-colon">:</td>
                <td class="info-val">{{ $payslip->month_calendar_days }}</td>

                <td class="info-label">Present Days</td>
                <td class="info-colon">:</td>
                <td class="info-val">{{ $payslip->days_worked }}</td>
            </tr>
            <tr>
                <td class="info-label">DOJ</td>
                <td class="info-colon">:</td>
                <td class="info-val">{{ $emp->joining_date ? $emp->joining_date->format('d/m/Y') : '01/04/2022' }}</td>

                <td class="info-label">Department</td>
                <td class="info-colon">:</td>
                <td class="info-val">{{ strtoupper($emp->department?->name ?? 'INFORMATION TECHNOLOGY') }}</td>
            </tr>
            <tr>
                <td class="info-label">Unit</td>
                <td class="info-colon">:</td>
                <td class="info-val">{{ strtoupper($comp?->code ?: 'CORPORATE HO') }}</td>

                <td class="info-label">Branch</td>
                <td class="info-colon">:</td>
                <td class="info-val">{{ strtoupper($emp->location?->city ?: ($comp?->city ?: 'GUWAHATI')) }}</td>
            </tr>
            <tr>
                <td class="info-label">Region</td>
                <td class="info-colon">:</td>
                <td class="info-val">{{ strtoupper($emp->location?->name ?: 'NORTH EAST REGION') }}</td>

                <td class="info-label">A/c No.</td>
                <td class="info-colon">:</td>
                <td class="info-val">{{ $payslip->bank_account_number ?: ($emp->account_number ?: '0327200100001473') }}</td>
            </tr>
            <tr>
                <td class="info-label">Pan No.</td>
                <td class="info-colon">:</td>
                <td class="info-val">{{ strtoupper($emp->pan_number ?: 'DSPPD6573R') }}</td>

                <td class="info-label">Mode of Pay</td>
                <td class="info-colon">:</td>
                <td class="info-val">{{ strtoupper($payslip->bank_name ?: ($emp->bank_name ?: 'BANK TRANSFER')) }}</td>
            </tr>
            <tr>
                <td class="info-label">Father Name</td>
                <td class="info-colon">:</td>
                <td class="info-val">{{ strtoupper($emp->father_or_husband_name ?: 'THANESWAR DEKA') }}</td>

                <td class="info-label">DOB</td>
                <td class="info-colon">:</td>
                <td class="info-val">{{ $emp->dob ? $emp->dob->format('d/m/Y') : '26/02/1999' }}</td>
            </tr>
            <tr>
                <td class="info-label">UAN NO.</td>
                <td class="info-colon">:</td>
                <td class="info-val">{{ $emp->uan_number ?: '101245896321' }}</td>

                {{-- User requested: "istead of func title add designation here" --}}
                <td class="info-label">Designation</td>
                <td class="info-colon">:</td>
                <td class="info-val"><strong>{{ strtoupper($emp->designation?->name ?? 'SOFTWARE ENGINEER') }}</strong></td>
            </tr>
            <tr>
                <td class="info-label">Level</td>
                <td class="info-colon">:</td>
                <td class="info-val">{{ $emp->user_type === 'admin' ? 'L-1 (Executive)' : 'L-2 (Associate)' }}</td>

                <td class="info-label">IFSC Code</td>
                <td class="info-colon">:</td>
                <td class="info-val">{{ strtoupper($payslip->ifsc_code ?: ($emp->ifsc_code ?: 'PUNB0032700')) }}</td>
            </tr>
        </table>

        <!-- EARNINGS AND DEDUCTIONS TABLE -->
        <table class="salary-table">
            <thead>
                <tr>
                    <th class="text-left" style="width: 28%;">Earnings</th>
                    <th class="text-right" style="width: 18%;">Theoretical</th>
                    <th class="text-right" style="width: 18%;">Amount</th>
                    <th class="text-left" style="width: 22%;">Deductions</th>
                    <th class="text-right" style="width: 14%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <!-- Row 1: Basic & PF -->
                <tr>
                    <td class="text-left">BASIC</td>
                    <td class="text-right">{{ number_format($payslip->basic_salary, 2) }}</td>
                    <td class="text-right">{{ number_format($payslip->basic_salary, 2) }}</td>
                    <td class="text-left">PF</td>
                    <td class="text-right">{{ number_format($payslip->pf_deduction, 2) }}</td>
                </tr>

                <!-- Row 2: HRA & ESI -->
                <tr>
                    <td class="text-left">HRA</td>
                    <td class="text-right">{{ number_format($payslip->hra_amount, 2) }}</td>
                    <td class="text-right">{{ number_format($payslip->hra_amount, 2) }}</td>
                    <td class="text-left">ESI</td>
                    <td class="text-right">{{ number_format($payslip->esic_deduction, 2) }}</td>
                </tr>

                <!-- Row 3: DA & Tax/PT -->
                <tr>
                    <td class="text-left">DA</td>
                    <td class="text-right">{{ number_format($payslip->da_amount, 2) }}</td>
                    <td class="text-right">{{ number_format($payslip->da_amount, 2) }}</td>
                    <td class="text-left">Tax / TDS</td>
                    <td class="text-right">{{ number_format($payslip->tax_deduction, 2) }}</td>
                </tr>

                <!-- Row 4: Conveyance & Leave Deduction -->
                <tr>
                    <td class="text-left">Conveyance</td>
                    <td class="text-right">{{ number_format($payslip->ca_amount, 2) }}</td>
                    <td class="text-right">{{ number_format($payslip->ca_amount, 2) }}</td>
                    <td class="text-left">
                        Leave Deduction
                        @if($payslip->total_deductible_days > 0)
                            <span style="font-size: 9px; display: block; color: #444;">({{ $payslip->total_deductible_days }}d @ {{ number_format($payslip->daily_take_home_rate, 2) }}/d)</span>
                        @endif
                    </td>
                    <td class="text-right" style="font-weight: {{ $payslip->leave_deduction_amount > 0 ? 'bold' : 'normal' }};">
                        {{ number_format($payslip->leave_deduction_amount, 2) }}
                    </td>
                </tr>

                <!-- Row 5: Special Allowance -->
                <tr>
                    <td class="text-left">Special Al</td>
                    <td class="text-right">{{ number_format($payslip->sa_amount, 2) }}</td>
                    <td class="text-right">{{ number_format($payslip->sa_amount, 2) }}</td>
                    <td class="text-left"></td>
                    <td class="text-right"></td>
                </tr>

                <!-- Row 6: Education Allowance -->
                <tr>
                    <td class="text-left">Education Al</td>
                    <td class="text-right">{{ number_format($payslip->ea_amount, 2) }}</td>
                    <td class="text-right">{{ number_format($payslip->ea_amount, 2) }}</td>
                    <td class="text-left"></td>
                    <td class="text-right"></td>
                </tr>

                <!-- Vertical Spacer to mimic exact traditional invoice/payslip paper proportion -->
                <tr class="salary-spacer-row">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>

                <!-- Totals Row -->
                <tr class="salary-totals-row">
                    <td class="text-left">Total</td>
                    <td class="text-right">{{ number_format($theoreticalGross, 2) }}</td>
                    <td class="text-right">{{ number_format($theoreticalGross, 2) }}</td>
                    <td class="text-left">Total</td>
                    <td class="text-right">{{ number_format($payslip->statutory_deductions + $payslip->leave_deduction_amount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- NET PAY & IN WORDS BOX -->
        <div class="net-pay-section">
            <div class="net-pay-row">
                <div class="net-pay-label">Net Pay</div>
                <div class="net-pay-amount">{{ number_format($payslip->net_salary, 2) }}</div>
            </div>
            <div class="in-words-row">
                <div class="in-words-label">In Words</div>
                <div class="in-words-val">{{ $inWords }}</div>
            </div>
        </div>

        <!-- SIGNATURE -->
        <div class="signature-section">
            <span class="signature-title">Signature</span>
        </div>

        <!-- ADVANCE / REIMBURSEMENT SUMMARY STRIP (Traditional format from sample) -->
        <div class="advance-strip">
            <table class="mini-table">
                <thead>
                    <tr>
                        <th>Advance</th>
                        <th>Taken</th>
                        <th>Op. Bal</th>
                        <th>EMI/Rct.</th>
                        <th>Cl. Bal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Salary Adv.</td>
                        <td>0.00</td>
                        <td>0.00</td>
                        <td>0.00</td>
                        <td>0.00</td>
                    </tr>
                </tbody>
            </table>

            <table class="mini-table">
                <thead>
                    <tr>
                        <th>Reim Name</th>
                        <th>Op. Bal</th>
                        <th>Alloted</th>
                        <th>Claimed</th>
                        <th>Cl. Bal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Medical / Fuel</td>
                        <td>0.00</td>
                        <td>0.00</td>
                        <td>0.00</td>
                        <td>0.00</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- SYSTEM GENERATED DISCLAIMER -->
        <div class="system-footer">
            ............This sheet computer generated signature not required...............
        </div>

    </div>

</body>
</html>
