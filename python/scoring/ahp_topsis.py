import numpy as np
import pandas as pd

# --------------------------------------------------
# AHP WEIGHT CALCULATION
# --------------------------------------------------
def compute_ahp_weights(pairwise_matrix):
    """
    pairwise_matrix : numpy array (n x n)
    returns normalized AHP weights
    """
    eigvals, eigvecs = np.linalg.eig(pairwise_matrix)

    # principal eigenvector
    max_idx = np.argmax(np.real(eigvals))
    weights = np.real(eigvecs[:, max_idx])

    # normalize
    weights = np.abs(weights)
    weights = weights / weights.sum()

    return weights


# --------------------------------------------------
# TOPSIS METHOD
# --------------------------------------------------
def topsis(decision_df, weights):
    """
    decision_df : pandas DataFrame
    weights     : numpy array
    returns TOPSIS closeness scores
    """

    X = decision_df.values.astype(float)

    # normalization
    norm = np.sqrt((X ** 2).sum(axis=0))
    R = X / (norm + 1e-12)

    # weighted matrix
    V = R * weights

    # ideal best & worst
    pis = V.max(axis=0)
    nis = V.min(axis=0)

    # distances
    dist_pos = np.sqrt(((V - pis) ** 2).sum(axis=1))
    dist_neg = np.sqrt(((V - nis) ** 2).sum(axis=1))

    # closeness coefficient
    closeness = dist_neg / (dist_pos + dist_neg + 1e-12)

    return closeness


# --------------------------------------------------
# BUILD CRITERIA VECTOR (DB → ML)
# --------------------------------------------------
def build_criteria_vector(student_row):
    """
    student_row : dict from database
    order must match TOPSIS columns
    """

    return [
        float(student_row.get('cgpa', 0) or 0),
        int(student_row.get('coding_score', 0) or 0),
        int(student_row.get('projects_count', 0) or 0),
        int(student_row.get('hackathon_points', 0) or 0),
        int(student_row.get('internship_days', 0) or 0),
        int(student_row.get('sports_points', 0) or 0)
    ]
