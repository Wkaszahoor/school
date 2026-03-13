import { PBLRubric, RubricCriterion } from '@/types/professional-development';
import { useState } from 'react';

interface Props {
  rubric: PBLRubric;
  isEditable?: boolean;
  scores?: Record<number, number>;
  onScoreChange?: (criterionId: number, score: number) => void;
}

export default function PBLRubricDisplay({ rubric, isEditable = false, scores = {}, onScoreChange }: Props) {
  const [localScores, setLocalScores] = useState<Record<number, number>>(scores);

  const handleScoreChange = (criterionId: number, value: string) => {
    const score = parseFloat(value) || 0;
    const maxPoints = rubric.criteria.find(c => c.id === criterionId)?.max_points || 0;
    const validScore = Math.min(Math.max(score, 0), maxPoints);

    setLocalScores(prev => ({ ...prev, [criterionId]: validScore }));
    onScoreChange?.(criterionId, validScore);
  };

  const totalScore = Object.values(localScores).reduce((a, b) => a + b, 0);
  const maxTotalScore = rubric.criteria.reduce((a, c) => a + c.max_points, 0);

  return (
    <div className="space-y-4">
      <div className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b-2 border-gray-300">
              <th className="text-left py-3 px-4 font-semibold text-gray-900">Criterion</th>
              <th className="text-center py-3 px-4 font-semibold text-gray-900 w-24">Max Points</th>
              {isEditable && (
                <th className="text-center py-3 px-4 font-semibold text-gray-900 w-24">Score</th>
              )}
            </tr>
          </thead>
          <tbody>
            {rubric.criteria.map((criterion, idx) => (
              <tr key={criterion.id} className={`border-b ${idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'}`}>
                <td className="py-3 px-4">
                  <div>
                    <p className="font-medium text-gray-900">{criterion.criterion_name}</p>
                    {criterion.description && (
                      <p className="text-xs text-gray-600 mt-1">{criterion.description}</p>
                    )}
                  </div>
                </td>
                <td className="text-center py-3 px-4 font-medium text-gray-900">
                  {criterion.max_points}
                </td>
                {isEditable && (
                  <td className="text-center py-3 px-4">
                    <input
                      type="number"
                      min="0"
                      max={criterion.max_points}
                      value={localScores[criterion.id] || 0}
                      onChange={(e) => handleScoreChange(criterion.id, e.target.value)}
                      className="w-20 px-2 py-1 border border-gray-300 rounded-md text-center focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {isEditable && (
        <div className="bg-blue-50 p-4 rounded-lg border border-blue-200">
          <div className="flex justify-between items-center">
            <span className="font-semibold text-gray-900">Total Score:</span>
            <span className="text-xl font-bold text-blue-600">
              {totalScore} / {maxTotalScore}
            </span>
          </div>
          {maxTotalScore > 0 && (
            <div className="mt-2">
              <div className="w-full bg-gray-200 rounded-full h-2">
                <div
                  className="bg-blue-500 h-2 rounded-full transition-all"
                  style={{ width: `${(totalScore / maxTotalScore) * 100}%` }}
                />
              </div>
              <p className="text-xs text-gray-600 mt-1">
                {((totalScore / maxTotalScore) * 100).toFixed(1)}%
              </p>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
