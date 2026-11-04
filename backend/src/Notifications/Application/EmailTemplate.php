<?php

declare(strict_types=1);

namespace Tutorias\Notifications\Application;

final class EmailTemplate
{
    public static function render(string $name, string $title, string $message, string $url): string
    {
        $e = static fn (string $s) => htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return <<<HTML
        <!doctype html>
        <html lang="es"><body style="margin:0;background:#f3f6fa;font-family:Segoe UI,Arial,sans-serif;color:#1d2939">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:32px 0">
            <tr><td align="center">
              <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden">
                <tr><td style="background:#003b71;color:#ffffff;padding:20px 28px;font-size:15px;letter-spacing:.3px">
                  <strong>Tutorías UNIMINUTO</strong> · Ciencias Básicas, sede Zipaquirá
                </td></tr>
                <tr><td style="padding:28px">
                  <p style="margin:0 0 6px;color:#667085;font-size:14px">Hola, {$e($name)}</p>
                  <h1 style="margin:0 0 16px;font-size:20px;line-height:1.3">{$e($title)}</h1>
                  <p style="margin:0 0 24px;font-size:15px;line-height:1.6">{$e($message)}</p>
                  <a href="{$e($url)}" style="display:inline-block;background:#f7b500;color:#1d2939;text-decoration:none;font-weight:600;padding:12px 20px;border-radius:8px">Abrir en el sistema</a>
                </td></tr>
                <tr><td style="padding:16px 28px;background:#f8fafc;color:#98a2b3;font-size:12px">
                  Recibes este correo porque tienes una cuenta en el Sistema de Tutorías. Tratamiento de datos conforme a la Ley 1581 de 2012.
                </td></tr>
              </table>
            </td></tr>
          </table>
        </body></html>
        HTML;
    }
}
