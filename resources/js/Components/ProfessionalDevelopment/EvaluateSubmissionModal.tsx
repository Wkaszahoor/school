import React, { useState } from 'react';
import Modal from '@/Components/Modal';
import PBLRubricScoringCard from './PBLRubricScoringCard';

interface RubricCriterion {
    id: number;
    criterion_name: string;
    max_points: number;
    description?: string;
}

interface Rubric {
    id: number;
    rubric_name: string;
    criteria: RubricCriterion[];
}

interface Submission {
    id: number;
    group_name: string;
    file_path: string;
    submitted_at: string;
}

interface EvaluateSubmissionModalProps {
    isOpen: boolean;
    onClose: () => void;
    submission: Submission | null;
    rubric: Rubric | null;
    onSubmit: (data: { submission_id: number; scores: Record<number, number>; feedback: string; overall_score: number }) => void;
    initialScores?: Record<number, number>;
    initialFeedback?: string;
    isLoading?: boolean;
}

export default function EvaluateSubmissionModal({
    isOpen, onClose, submission, rubric, onSubmit, initialScores = {}, initialFeedback = '', isLoading = false
}: EvaluateSubmissionModalProps) {
    const handleSubmit = (data: { scores: Record<number, number>; feedback: string; overall_score: number }) => {
        if (!submission) return;
        onSubmit({
            submission_id: submission.id,
            ...data,
        });
        onClose();
    };

    if (!submission || !rubric) return null;

    return (
        <Modal isOpen={isOpen} onClose={onClose} title={`Grade: ${submission.group_name}`} size="2xl">
            <div className="mb-4 pb-4 border-b border-gray-200">
                <p className="text-sm text-gray-600">
                    Submitted: {new Date(submission.submitted_at).toLocaleDateString('en-GB', {
                        day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
                    })}
                </p>
                <a href={submission.file_path} download className="text-sm text-blue-600 hover:text-blue-700 font-medium mt-2 inline-flex items-center gap-1">
                    Download submission →
                </a>
            </div>
            <PBLRubricScoringCard
                rubric={rubric}
                onSubmit={handleSubmit}
                initialScores={initialScores}
                initialFeedback={initialFeedback}
                isLoading={isLoading}
            />
        </Modal>
    );
}
