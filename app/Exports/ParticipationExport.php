<?php

namespace App\Exports;

use App\Models\Adventure;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ParticipationExport
{
    // Spalten-Indizes (1-basiert)
    private const COL_NAME       = 1;
    private const COL_ROLE       = 2;
    private const COL_LIST       = 3;
    private const COL_STATUS     = 4;
    private const COL_ERMAESS    = 5;
    private const COL_BETRAG     = 6;
    private const COL_PAID       = 7;
    private const COL_ANWESEND   = 8;
    private const COL_GUARDIAN   = 9;
    private const COL_CONTACT    = 10;

    public function __construct(private readonly Adventure $adventure) {}

    public function download(): StreamedResponse
    {
        $adventure = $this->adventure;
        $adventure->load(['bookings.player.users', 'bookings.role', 'bookings.bookedBy', 'visits']);
        $visitedIds = $adventure->visits->pluck('player_id');

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setTitle('Belegungsreport ' . $adventure->name)
            ->setCreator('Heldenregister')
            ->setSubject($adventure->name);

        // ── Blatt 1: Teilnehmerliste ──────────────────────────────────────────
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Teilnehmer');

        $this->buildParticipantSheet($sheet, $adventure, $visitedIds);

        // ── Blatt 2: Kassenübersicht ──────────────────────────────────────────
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Kassenübersicht');

        $this->buildCashSheet($sheet2, $adventure);

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'belegung-' . $adventure->id . '-' . now()->format('Ymd') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function buildParticipantSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        Adventure $adventure,
        \Illuminate\Support\Collection $visitedIds,
    ): void {
        // Titel
        $sheet->mergeCells('A1:J1');
        $sheet->setCellValue('A1', $adventure->name);
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        $date = $adventure->start_at?->format('d.m.Y') ?? '—';
        $sheet->mergeCells('A2:J2');
        $sheet->setCellValue('A2', 'Datum: ' . $date . '   |   Teilnahmebeitrag: ' . number_format((float) $adventure->fee, 2, ',', '.') . ' €'
            . ($adventure->fee_reduced !== null ? '   |   Ermäßigt: ' . number_format((float) $adventure->fee_reduced, 2, ',', '.') . ' €' : ''));
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);

        // Header-Zeile
        $headerRow = 4;
        $headers = [
            self::COL_NAME     => 'Name',
            self::COL_ROLE     => 'Rolle',
            self::COL_LIST     => 'Liste',
            self::COL_STATUS   => 'Status',
            self::COL_ERMAESS  => 'Ermäßigung',
            self::COL_BETRAG   => 'Betrag (€)',
            self::COL_PAID     => 'Bezahlt',
            self::COL_ANWESEND => 'Anwesend',
            self::COL_GUARDIAN => 'Erziehungsberechtigte/r',
            self::COL_CONTACT  => 'Kontaktnummer',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . $headerRow, $label);
        }

        $sheet->getStyle('A4:J4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5a3a22']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(18);

        // Datenzeilen
        $row = $headerRow + 1;
        foreach ($adventure->bookings as $b) {
            $fee = ($b->ermaessigung && $adventure->fee_reduced !== null)
                ? (float) $adventure->fee_reduced
                : (float) $adventure->fee;

            $guardian = $b->guardian();

            $sheet->setCellValue(Coordinate::stringFromColumnIndex(self::COL_NAME)     . $row, $b->participant_name . ($b->is_guest ? ' (Gast)' : ''));
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(self::COL_ROLE)     . $row, $b->role?->description ?? '—');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(self::COL_LIST)     . $row, $b->waitlisted ? 'Warteliste' : 'regulär');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(self::COL_STATUS)   . $row, $b->status_label);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(self::COL_ERMAESS)  . $row, $b->ermaessigung ? 'ja' : 'nein');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(self::COL_BETRAG)   . $row, $fee);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(self::COL_PAID)     . $row, $b->paid ? 'ja' : 'nein');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(self::COL_ANWESEND) . $row, $visitedIds->contains($b->player_id) ? 'ja' : 'nein');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(self::COL_GUARDIAN) . $row, $guardian ? trim($guardian->name . ' ' . $guardian->lastname) : '—');
            $sheet->setCellValue(Coordinate::stringFromColumnIndex(self::COL_CONTACT)  . $row, $b->kontakt_telefon ?? '—');

            // Betrag als Zahl formatieren
            $betragCell = Coordinate::stringFromColumnIndex(self::COL_BETRAG) . $row;
            $sheet->getStyle($betragCell)->getNumberFormat()->setFormatCode('#,##0.00 [$€-407]');

            // Zebrastreifen
            if ($row % 2 === 0) {
                $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FDF6E3']],
                ]);
            }

            // Warteliste grau
            if ($b->waitlisted) {
                $sheet->getStyle('A' . $row . ':J' . $row)->getFont()->setColor(
                    new \PhpOffice\PhpSpreadsheet\Style\Color('FF888888')
                );
            }

            // Bezahlt grün markieren
            $paidCell = Coordinate::stringFromColumnIndex(self::COL_PAID) . $row;
            if ($b->paid) {
                $sheet->getStyle($paidCell)->getFont()->setColor(
                    new \PhpOffice\PhpSpreadsheet\Style\Color('FF1a7a1a')
                );
            }

            $row++;
        }

        // Rahmen um Datentabelle
        $lastDataRow = $row - 1;
        if ($lastDataRow >= $headerRow) {
            $sheet->getStyle('A' . $headerRow . ':J' . $lastDataRow)->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']],
                    'outline'    => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '5a3a22']],
                ],
            ]);
        }

        // Spaltenbreiten
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex(self::COL_NAME))->setWidth(28);
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex(self::COL_ROLE))->setWidth(14);
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex(self::COL_LIST))->setWidth(12);
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex(self::COL_STATUS))->setWidth(14);
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex(self::COL_ERMAESS))->setWidth(13);
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex(self::COL_BETRAG))->setWidth(13);
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex(self::COL_PAID))->setWidth(10);
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex(self::COL_ANWESEND))->setWidth(11);
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex(self::COL_GUARDIAN))->setWidth(26);
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex(self::COL_CONTACT))->setWidth(18);

        $sheet->freezePane('A' . ($headerRow + 1));
        $sheet->setAutoFilter('A4:J4');
    }

    private function buildCashSheet(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        Adventure $adventure,
    ): void {
        $summary = $adventure->paymentSummary();
        $payable = $adventure->bookings->where('waitlisted', false);

        // Titel
        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue('A1', 'Kassenübersicht – ' . $adventure->name);
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13],
        ]);

        $sheet->setCellValue('A2', 'Stand: ' . now()->format('d.m.Y H:i'));
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9);

        // Abschnitt: Beitragsübersicht
        $row = 4;
        $this->cashSectionHeader($sheet, $row, 'Beitragsübersicht');
        $row++;

        $this->cashRow($sheet, $row++, 'Regulärer Beitrag', number_format((float) $adventure->fee, 2, ',', '.') . ' €');
        if ($adventure->fee_reduced !== null) {
            $this->cashRow($sheet, $row++, 'Ermäßigter Beitrag', number_format((float) $adventure->fee_reduced, 2, ',', '.') . ' €');
        }

        $row++;

        // Abschnitt: Teilnehmer
        $this->cashSectionHeader($sheet, $row, 'Teilnehmer');
        $row++;

        $this->cashRow($sheet, $row++, 'Reguläre Plätze', $payable->count());
        $this->cashRow($sheet, $row++, 'Warteliste', $adventure->bookings->where('waitlisted', true)->count());
        if ($adventure->fee_reduced !== null) {
            $this->cashRow($sheet, $row++, 'davon ermäßigt', $summary['erm_count']);
            $this->cashRow($sheet, $row++, 'davon regulär', $summary['total_count'] - $summary['erm_count']);
        }
        $this->cashRow($sheet, $row++, 'Anwesend', $adventure->visits->count());

        $row++;

        // Abschnitt: Zahlungsstatus
        $this->cashSectionHeader($sheet, $row, 'Zahlungsstatus');
        $row++;

        $this->cashRow($sheet, $row++, 'Bezahlt (Anzahl)', $summary['paid_count']);
        $this->cashRow($sheet, $row++, 'Offen (Anzahl)', $summary['total_count'] - $summary['paid_count']);

        $row++;

        // Abschnitt: Beträge
        $this->cashSectionHeader($sheet, $row, 'Beträge');
        $row++;

        $this->cashAmountRow($sheet, $row++, 'Eingegangen', $summary['paid_amount'], '1a7a1a');
        $this->cashAmountRow($sheet, $row++, 'Noch offen', $summary['open_amount'], $summary['open_amount'] > 0 ? 'b45309' : '888888');
        $this->cashAmountRow($sheet, $row++, 'Gesamt erwartet', $summary['total_amount'], '000000', true);

        // Spaltenbreiten
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(5);
    }

    private function cashSectionHeader(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $row, string $label): void
    {
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->setCellValue('A' . $row, $label);
        $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '5a3a22']],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(16);
    }

    private function cashRow(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $row, string $label, mixed $value): void
    {
        $sheet->setCellValue('A' . $row, $label);
        $sheet->setCellValue('B' . $row, $value);
        $sheet->getStyle('A' . $row)->getFont()->setBold(false);
        $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    }

    private function cashAmountRow(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        int $row,
        string $label,
        float $amount,
        string $colorRgb,
        bool $bold = false,
    ): void {
        $sheet->setCellValue('A' . $row, $label);
        $sheet->setCellValue('B' . $row, $amount);
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0.00 [$€-407]');
        $sheet->getStyle('B' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF' . $colorRgb));
        if ($bold) {
            $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
        }
    }
}
