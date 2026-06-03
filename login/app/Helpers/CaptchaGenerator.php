<?php

namespace App\Helpers;

class CaptchaGenerator
{
    /**
     * Generar un nuevo captcha, guardarlo en la sesión y retornar la cadena SVG.
     *
     * @param string $sessionKey
     * @return string
     */
    public static function generate(string $sessionKey = 'login_captcha'): string
    {
        // Generar una cadena aleatoria de 5 caracteres (letras y números)
        // Evitar caracteres confusos como I, O, 0, 1, l
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 5; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Guardar el código en la sesión (en minúsculas para validación insensible a mayúsculas)
        session([$sessionKey => strtolower($code)]);
        
        // Crear una representación SVG del código
        $width = 160;
        $height = 50;
        
        $svg = "<svg width='{$width}' height='{$height}' xmlns='http://www.w3.org/2000/svg' style='background: #f8fafc; border-radius: 0.75rem; border: 1px solid #e2e8f0; user-select: none;'>";
        
        // Agregar cuadrícula/líneas de ruido de fondo aleatorias
        for ($i = 0; $i < 5; $i++) {
            $x1 = rand(0, $width);
            $y1 = rand(0, $height);
            $x2 = rand(0, $width);
            $y2 = rand(0, $height);
            $colors = ['#cbd5e1', '#94a3b8', '#e2e8f0', '#f1f5f9'];
            $color = $colors[array_rand($colors)];
            $svg .= "<line x1='{$x1}' y1='{$y1}' x2='{$x2}' y2='{$y2}' stroke='{$color}' stroke-width='1.5' />";
        }
        
        // Agregar caracteres con desfases, rotación y tamaños de fuente aleatorios
        for ($i = 0; $i < strlen($code); $i++) {
            $char = $code[$i];
            $x = 22 + ($i * 26) + rand(-2, 2);
            $y = 34 + rand(-4, 4);
            $angle = rand(-15, 15);
            $fontSize = rand(22, 26);
            $colors = ['#4f46e5', '#312e81', '#1e3a8a', '#0369a1', '#0f766e', '#1d4ed8'];
            $color = $colors[array_rand($colors)];
            
            $svg .= "<text x='{$x}' y='{$y}' font-family='monospace, sans-serif' font-size='{$fontSize}' font-weight='bold' fill='{$color}' transform='rotate({$angle} {$x} {$y})'>{$char}</text>";
        }
        
        // Agregar algunos puntos de ruido
        for ($i = 0; $i < 40; $i++) {
            $cx = rand(0, $width);
            $cy = rand(0, $height);
            $r = rand(1, 2);
            $svg .= "<circle cx='{$cx}' cy='{$cy}' r='{$r}' fill='#94a3b8' opacity='0.5' />";
        }
        
        $svg .= "</svg>";
        
        return $svg;
    }
}
