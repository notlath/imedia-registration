<?php

/**
 * IMedia Registration — ExportController.
 *
 * Phase 3: 4 CSV streams (registrations, contacts, applications-ojt,
 * applications-trainer). Uses fputcsv() over php://output. Phase 5
 * (Excel export) is not in scope.
 *
 * Per php-pro: strict types, readonly controller.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Config, Database, Request, Response};
use App\Models\{Application, Contact, Registration};

final readonly class ExportController
{
    public function stream(Request $req, Response $res): Response
    {
        $type = (string) $req->param('type');
        $date = date('Y-m-d');

        return match ($type) {
            'registrations' => $this->streamRegistrations($res, $date),
            'contacts'      => $this->streamContacts($res, $date),
            'applications-ojt'     => $this->streamApplications($res, $date, Application::TYPE_OJT),
            'applications-trainer' => $this->streamApplications($res, $date, Application::TYPE_TRAINER),
            default => $res->error(404, 'Unknown export type: ' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8')),
        };
    }

    private function streamRegistrations(Response $res, string $date): Response
    {
        $filename = "registrations-{$date}.csv";
        $res->header('Content-Type', 'text/csv; charset=utf-8');
        $res->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $res->header('X-Accel-Buffering', 'no');

        $out = fopen('php://output', 'wb');
        if ($out === false) {
            return $res->error(500, 'Could not open output stream.');
        }
        fputcsv($out, [
            'id', 'name', 'mobile', 'email', 'address', 'course',
            'start_date', 'end_date', 'status', 'payment_status',
            'paid_amount', 'paid_at', 'remark', 'dynamic_data',
            'created_at', 'updated_at',
        ]);
        foreach (Registration::allForExport() as $row) {
            fputcsv($out, [
                $row['id'],
                $row['name'],
                $row['mobile'] ?? '',
                $row['email'],
                $row['address'] ?? '',
                $row['course'],
                $row['start_date'],
                $row['end_date'],
                $row['status'],
                $row['payment_status'],
                $row['paid_amount'] ?? '',
                $row['paid_at'] ?? '',
                $row['remark'] ?? '',
                json_encode($row['dynamic_data'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                $row['created_at'],
                $row['updated_at'],
            ]);
        }
        fclose($out);
        return $res;
    }

    private function streamContacts(Response $res, string $date): Response
    {
        $filename = "contacts-{$date}.csv";
        $res->header('Content-Type', 'text/csv; charset=utf-8');
        $res->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $res->header('X-Accel-Buffering', 'no');

        $out = fopen('php://output', 'wb');
        if ($out === false) {
            return $res->error(500, 'Could not open output stream.');
        }
        fputcsv($out, ['id', 'name', 'mobile', 'email', 'subject', 'message', 'status', 'remarks', 'created_at']);
        Database::unbuffered(function () use ($out) {
            foreach (Contact::all() as $row) {
                fputcsv($out, [
                    $row['id'], $row['name'], $row['mobile'], $row['email'],
                    $row['subject'], $row['message'], $row['status'], $row['remarks'],
                    $row['created_at'],
                ]);
            }
        });
        fclose($out);
        return $res;
    }

    private function streamApplications(Response $res, string $date, string $type): Response
    {
        $filename = "applications-{$type}-{$date}.csv";
        $res->header('Content-Type', 'text/csv; charset=utf-8');
        $res->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $res->header('X-Accel-Buffering', 'no');

        $out = fopen('php://output', 'wb');
        if ($out === false) {
            return $res->error(500, 'Could not open output stream.');
        }
        fputcsv($out, ['id', 'type', 'name', 'mobile', 'email', 'position', 'message', 'resume_filename', 'status', 'remarks', 'created_at']);
        Database::unbuffered(function () use ($out, $type) {
            foreach (Application::allByType($type) as $row) {
                fputcsv($out, [
                    $row['id'], $row['type'], $row['name'], $row['mobile'],
                    $row['email'], $row['position'], $row['message'],
                    $row['resume_filename'], $row['status'], $row['remarks'],
                    $row['created_at'],
                ]);
            }
        });
        fclose($out);
        return $res;
    }
}
