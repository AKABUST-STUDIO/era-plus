<?php

namespace App\Filament\Panels\Actions;

use App\Models\Feedback;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Support\HtmlString;

class FeedbackAction
{
    public static function make(string $name = 'feedback'): Action
    {
        return Action::make($name)
            ->label(__('user.feedback.action'))
            ->icon('lucide-smile')
            ->modalIcon('lucide-smile')
            ->modalHeading(__('user.feedback.heading'))
            ->modalDescription(__('user.feedback.description'))
            ->modalWidth(Width::Large)
            ->modalCloseButton(false)
            ->modalCancelAction(false)
            ->modalFooterActionsAlignment(Alignment::End)
            ->modalSubmitActionLabel(__('user.feedback.submit'))
            ->schema([
                ViewField::make('rating')
                    ->hiddenLabel()
                    ->view('filament.forms.components.star-rating')
                    ->default(0),
                TextInput::make('subject')
                    ->hiddenLabel()
                    ->placeholder(__('user.feedback.subject'))
                    ->maxLength(255),
                Textarea::make('description')
                    ->hiddenLabel()
                    ->placeholder(__('user.feedback.description_field'))
                    ->helperText(fn (): HtmlString => new HtmlString(__('user.feedback.terms', [
                        'terms' => '<a href="'.route('legal.terms').'" target="_blank" class="underline">'.e(__('user.feedback.terms_link')).'</a>',
                        'policy' => '<a href="'.route('legal.privacy').'" target="_blank" class="underline">'.e(__('user.feedback.policy_link')).'</a>',
                    ])))
                    ->rows(5),
            ])
            ->action(function (array $data): void {
                Feedback::create([
                    'user_id' => auth()->id(),
                    'subject' => $data['subject'] ?? null,
                    'rating' => filled($data['rating'] ?? null) ? (int) $data['rating'] : null,
                    'description' => $data['description'] ?? null,
                ]);

                Notification::make()
                    ->title(__('user.feedback.submitted'))
                    ->success()
                    ->send();
            });
    }
}
