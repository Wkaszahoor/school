import React, { useState, useEffect } from 'react';

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

interface PBLRubricScoringCardProps {
    rubric: Rubric;
    onSubmit: (data: { scores: Record<number, number>; feedback: string; overall_score: number }) => void;
    initialScores?: Record<number, number>;
    initialFeedback?: string;
    isLoading?: boolean;
}

export default function PBLRubricScoringCard({
    rubric, onSubmit, initialScores = {}, initialFeedback = '', isLoading = false
}: PBLRubricScoringCardProps) {
    const [scores, setScores] = useState<Record<number, number>>(initialScores);
    const [feedback, setFeedback] = useState(initialFeedback);

    useEffect(() => {
        // Initialize scores for all criteria
        const newScores: Record<number, number> = {};
        rubric.criteria.forEach(criterion => {
            newScores[criterion.id] = initialScores[criterion.id] || 0;
        });
        setScores(newScores);
    }, [rubric, initialScores]);

    const handleScoreChange = (criterionId: number, value: number) => {
        setScores(prev => ({ ...prev, [criterionId]: Math.max(0, value) }));
    };

    const overallScore = Object.values(scores).reduce((sum, score) => sum + score, 0);
    const maxTotalScore = rubric.criteria.reduce((sum, criterion) => sum + criterion.max_points, 0);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        onSubmit({
            scores,
            feedback,
            overall_score: overallScore,
        });
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <div className="border-t border-gray-200">
                <div className="space-y-4 py-4">
                    {rubric.criteria.map(criterion => (
                        <div key={criterion.id} className="flex flex-col gap-2">
                            <div className="flex items-start justify-between">
                                <div>
                                    <label className="font-medium text-gray-900">{criterion.criterion_name}</label>
                                    {criterion.description && (
                                        <p className="text-sm text-gray-600 mt-1">{criterion.description}</p>
                                    )}
                                </div>
                                <span className="text-xs font-semibold text-gray-500">Max: {criterion.max_points}</span>
                            </div>
                            <input
                                type="number"
                                min="0"
                                max={criterion.max_points}
                                value={scores[criterion.id] || 0}
                                onChange={(e) => handleScoreChange(criterion.id, parseInt(e.target.value) || 0)}
                                className="form-input"
                                placeholder="Enter score"
                            />
                        </div>
                    ))}
                </div>
            </div>

            {/* Overall Score Summary */}
            <div className="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <div className="flex items-center justify-between">
                    <span className="font-medium text-gray-900">Overall Score</span>
                    <span className="text-2xl font-bold text-blue-600">
                        {overallScore} / {maxTotalScore}
                    </span>
                </div>
            </div>

            {/* Feedback */}
            <div>
                <label htmlFor="feedback" className="block text-sm font-medium text-gray-900 mb-2">
                    Feedback
                </label>
                <textarea
                    id="feedback"
                    value={feedback}
                    onChange={(e) => setFeedback(e.target.value)}
                    className="form-textarea"
                    rows={4}
                    placeholder="Enter feedback for the student group…"
                />
            </div>

            {/* Submit Button */}
            <div className="flex gap-2 justify-end pt-4 border-t border-gray-200">
                <button
                    type="submit"
                    disabled={isLoading}
                    className="btn btn-primary"
                >
                    {isLoading ? 'Submitting…' : 'Submit Grade'}
                </button>
            </div>
        </form>
    );
}
