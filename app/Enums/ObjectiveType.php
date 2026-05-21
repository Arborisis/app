<?php

declare(strict_types=1);

namespace App\Enums;

enum ObjectiveType: string
{
    case VisitPoints = 'visit_points';
    case VisitCategory = 'visit_category';
    case CreatePoint = 'create_point';
    case UploadSound = 'upload_sound';
    case ListenSound = 'listen_sound';
    case ReceiveLike = 'receive_like';
    case Comment = 'comment';
    case ReportValidIssue = 'report_valid_issue';
    case LoginStreak = 'login_streak';
    case CompleteProfile = 'complete_profile';
    case VisitUniqueRegions = 'visit_unique_regions';

    public function label(): string
    {
        return match ($this) {
            self::VisitPoints => 'Visiter des points',
            self::VisitCategory => 'Visiter une catégorie',
            self::CreatePoint => 'Créer un point',
            self::UploadSound => 'Publier un son',
            self::ListenSound => 'Écouter des sons',
            self::ReceiveLike => 'Recevoir des likes',
            self::Comment => 'Commenter',
            self::ReportValidIssue => 'Signaler un problème valide',
            self::LoginStreak => 'Série de connexions',
            self::CompleteProfile => 'Compléter le profil',
            self::VisitUniqueRegions => 'Visiter des régions uniques',
        };
    }
}
